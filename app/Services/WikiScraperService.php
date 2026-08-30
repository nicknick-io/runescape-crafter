<?php

namespace App\Services;

use App\Models\Item;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use function Laravel\Ai\agent;

class WikiScraperService
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'RuneScape Recipe Scraper/1.0',
            ],
        ]);
    }

    /**
     * Scrape recipe data from RuneScape wiki URL using Laravel AI SDK + OpenAI
     * Checks if recipe already exists before making expensive AI API calls
     *
     * @param string $wikiUrl
     * @return array{
     *     output_item_name: string,
     *     output_item_id: int|null,
     *     skill: string,
     *     level_required: int,
     *     xp: float,
     *     output_quantity: int,
     *     ingredients: array<array{item_name: string, item_id: int|null, quantity: float}>,
     *     tool_name: string|null,
     *     tool_id: int|null,
     *     confidence_score: float,
     *     already_exists: bool,
     * }
     */
    public function scrapeRecipe(string $wikiUrl): array
    {
        try {
            // Extract potential item name from URL to check if recipe already exists
            $potentialItemName = $this->extractItemNameFromUrl($wikiUrl);
            
            if ($potentialItemName) {
                $existingRecipe = $this->checkRecipeExists($potentialItemName);
                if ($existingRecipe) {
                    return [
                        'output_item_name' => $existingRecipe->outputItem?->name ?? $potentialItemName,
                        'output_item_id' => $existingRecipe->output_item_id,
                        'skill' => $existingRecipe->skill,
                        'level_required' => $existingRecipe->level_required,
                        'xp' => (float) $existingRecipe->xp,
                        'output_quantity' => $existingRecipe->output_quantity,
                        'ingredients' => $existingRecipe->ingredients->map(fn ($ing) => [
                            'item_name' => $ing->item?->name ?? 'Unknown',
                            'item_id' => $ing->item_id,
                            'quantity' => (float) $ing->quantity,
                        ])->toArray(),
                        'tool_name' => $existingRecipe->tool?->name,
                        'tool_id' => $existingRecipe->tool_id,
                        'confidence_score' => 1.0,
                        'already_exists' => true,
                    ];
                }
            }

            // Fetch the wiki page content
            $pageContent = $this->fetchWikiPage($wikiUrl);

            // Extract recipe data using Laravel AI SDK (OpenAI)
            $recipeData = $this->extractRecipeDataWithAI($pageContent);

            // Try to match items in database
            $recipeData['output_item_id'] = $this->findItemIdByName($recipeData['output_item_name']);
            $recipeData['ingredients'] = $this->matchIngredientsWithDatabase($recipeData['ingredients']);
            
            // Extract and match tool if mentioned
            $toolName = $this->extractToolNameWithAI($pageContent);
            $recipeData['tool_name'] = $toolName;
            $recipeData['tool_id'] = $toolName ? $this->findItemIdByName($toolName, isToolSearch: true) : null;
            
            $recipeData['already_exists'] = false;
            $recipeData['wiki_url'] = $wikiUrl;

            return $recipeData;
        } catch (\Exception $e) {
            Log::error('Wiki scraping failed', [
                'url' => $wikiUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to scrape wiki page: {$e->getMessage()}");
        }
    }

    /**
     * Extract item name from wiki URL
     * e.g., "https://runescape.wiki/w/Elder_rune_bar" -> "Elder rune bar"
     */
    private function extractItemNameFromUrl(string $url): ?string
    {
        if (preg_match('/\/w\/([^\/\?]+)/', $url, $matches)) {
            $slug = $matches[1];
            // Replace underscores with spaces and decode URL encoding
            return urldecode(str_replace('_', ' ', $slug));
        }
        return null;
    }

    /**
     * Fetch wiki page content
     */
    private function fetchWikiPage(string $wikiUrl): string
    {
        $response = $this->httpClient->get($wikiUrl, [
            'allow_redirects' => true,
        ]);

        return (string) $response->getBody();
    }

    /**
     * Extract recipe data using Laravel AI SDK with OpenAI provider
     * https://laravel.com/docs/ai-sdk
     */
    private function extractRecipeDataWithAI(string $pageContent): array
    {
        $prompt = <<<'PROMPT'
You are a RuneScape wiki page parser. Extract recipe/crafting information from the provided HTML content.

Extract the following information in JSON format only (no other text):
{
  "output_item_name": "exact name of the crafted item",
  "skill": "Crafting, Smithing, Cooking, Herblore, etc.",
  "level_required": 1,
  "xp": 10.5,
  "output_quantity": 1,
  "ingredients": [
    {"item_name": "exact ingredient name", "quantity": 1.0}
  ],
  "confidence_score": 0.95
}

Rules:
- Use exact item names from the wiki
- For quantity, use decimal format (e.g., 2.5 for half-ore recipes)
- confidence_score: 1.0 = very confident, 0.5 = moderate, 0.0 = couldn't extract
- If this is not a recipe page, return confidence_score 0
- Extract ALL ingredients listed
- Return ONLY valid JSON, no markdown or extra text

HTML Content to parse:
PROMPT;

        $prompt .= "\n\n" . $this->sanitizeHtml($pageContent);

        try {
            // Use Laravel AI SDK with default OpenAI provider
            // The framework reads OPENAI_API_KEY from config/ai.php or .env
            $response = agent()->prompt($prompt);

            $text = (string) $response;

            // Extract JSON from response (in case model adds extra text)
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $text = $matches[0];
            }

            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                throw new \RuntimeException('Invalid JSON response from OpenAI');
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('OpenAI extraction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException("OpenAI API error: {$e->getMessage()}");
        }
    }

    /**
     * Sanitize HTML to reasonable size for API (limit to first 8000 chars of meaningful content)
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/i', '', $html);

        // Remove HTML tags but keep structure
        $html = strip_tags($html, '<table><tr><td><p><h1><h2><h3><h4><div><span><li><ul>');

        // Remove extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);

        // Limit to 8000 chars
        return substr($html, 0, 8000);
    }

    /**
     * Find item ID by name with intelligent fuzzy matching
     * @param bool $isToolSearch Whether to search for tools (skip suffix stripping for better tool matching)
     */
    private function findItemIdByName(string $itemName, bool $isToolSearch = false): ?int
    {
        if (empty(trim($itemName))) {
            return null;
        }

        // Try exact match first (case-insensitive)
        $item = Item::whereRaw('LOWER(name) = ?', [strtolower($itemName)])
            ->first();
        
        if ($item) {
            return $item->id;
        }

        // For tools, be more strict - avoid suffix stripping for better precision
        if (!$isToolSearch) {
            // Try removing common suffixes (ore, bar, sheet, etc.)
            $cleanName = $this->stripCommonSuffixes($itemName);
            $item = Item::whereRaw('LOWER(name) LIKE ?', ["%$cleanName%"])
                ->first();
            
            if ($item) {
                return $item->id;
            }
        }

        // Try fuzzy match: check if extracted name's first/important words exist in DB names
        $words = preg_split('/\s+/', trim($itemName), -1, PREG_SPLIT_NO_EMPTY);
        
        if (!empty($words)) {
            // Start with the most specific search
            foreach ($words as $word) {
                if (strlen($word) > 2) { // Skip very short words
                    $item = Item::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($word) . '%'])
                        ->orderByRaw('CHAR_LENGTH(name)') // Prefer shorter names (more specific)
                        ->first();
                    
                    if ($item) {
                        return $item->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Strip common suffixes to improve matching
     * e.g., "light animica ore" -> "light animica"
     */
    private function stripCommonSuffixes(string $name): string
    {
        $suffixes = ['ore', 'bar', 'sheet', 'plate', 'dart', 'arrow', 'bolts', 'logs', 'planks'];
        
        foreach ($suffixes as $suffix) {
            $pattern = '/\s+' . preg_quote($suffix, '/') . '$/i';
            if (preg_match($pattern, $name)) {
                return preg_replace($pattern, '', $name);
            }
        }
        
        return $name;
    }

    /**
     * Extract tool name using AI from wiki page content
     * e.g., "Chisel", "Hammer", "Needle", etc.
     */
    private function extractToolNameWithAI(string $pageContent): ?string
    {
        $prompt = <<<'PROMPT'
You are a RuneScape wiki page parser. From the provided HTML content, identify if a specific tool or equipment is required to create/craft the item.

Extract in JSON format only:
{
  "tool_name": "exact name of tool if found (Chisel, Hammer, Needle, Saw, etc.) or null"
}

Rules:
- Look for mentions of tools required for crafting (NOT ingredients, but equipment/tools)
- Return null if no tool is mentioned
- Use exact tool names as they appear in RuneScape (e.g., "Chisel", "Hammer")
- Return ONLY valid JSON, no extra text

HTML Content to parse:
PROMPT;

        $prompt .= "\n\n" . $this->sanitizeHtml($pageContent);

        try {
            $response = agent()->prompt($prompt);
            $text = (string) $response;

            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $text = $matches[0];
            }

            $decoded = json_decode($text, true);

            if (is_array($decoded) && isset($decoded['tool_name']) && $decoded['tool_name']) {
                return $decoded['tool_name'];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Tool extraction failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if recipe already exists for an item
     */
    private function checkRecipeExists(string $itemName): ?object
    {
        // Try exact match first
        $item = Item::whereRaw('LOWER(name) = ?', [strtolower($itemName)])
            ->with('recipe.ingredients.item', 'recipe.tool')
            ->first();

        if ($item && $item->recipe) {
            return $item->recipe;
        }

        // Try fuzzy match
        $item = Item::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($itemName) . '%'])
            ->with('recipe.ingredients.item', 'recipe.tool')
            ->first();

        if ($item && $item->recipe) {
            return $item->recipe;
        }

        return null;
    }

    /**
     * Match ingredients with database items
     *
     * @param array<array{item_name: string, quantity: float}> $ingredients
     * @return array<array{item_name: string, item_id: int|null, quantity: float}>
     */
    private function matchIngredientsWithDatabase(array $ingredients): array
    {
        return array_map(function (array $ingredient) {
            $item_id = $this->findItemIdByName($ingredient['item_name'] ?? '', isToolSearch: false);

            return [
                'item_name' => $ingredient['item_name'] ?? 'Unknown',
                'item_id' => $item_id,
                'quantity' => (float) ($ingredient['quantity'] ?? 1),
            ];
        }, $ingredients);
    }
}




