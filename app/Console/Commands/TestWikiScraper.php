<?php

namespace App\Console\Commands;

use App\Services\WikiScraperService;
use Illuminate\Console\Command;

class TestWikiScraper extends Command
{
    protected $signature = 'wiki:scrape {url : The RuneScape wiki URL to scrape}';
    protected $description = 'Test scraping a RuneScape wiki page using Laravel AI + OpenAI';

    public function handle(WikiScraperService $scraper)
    {
        $url = $this->argument('url');
        
        $this->info("Scraping: {$url}");
        $this->line('Using Laravel AI with OpenAI provider...');
        
        try {
            $result = $scraper->scrapeRecipe($url);
            
            if ($result['already_exists']) {
                $this->warn("\n⚠ Recipe already exists in database! (No API tokens used)");
            } else {
                $this->info("\n✓ Scraping successful!");
            }
            
            $this->line("\nExtracted Data:");
            $this->line("Output Item: {$result['output_item_name']} (ID: {$result['output_item_id']})");
            $this->line("Skill: {$result['skill']}");
            $this->line("Level: {$result['level_required']}");
            $this->line("XP: {$result['xp']}");
            $this->line("Output Qty: {$result['output_quantity']}");
            $this->line("Confidence: " . round($result['confidence_score'] * 100) . "%");
            
            if (!empty($result['tool_name'])) {
                $toolId = $result['tool_id'] ? " (ID: {$result['tool_id']})" : " (Not found)";
                $this->line("Tool: {$result['tool_name']}{$toolId}");
            }
            
            $wikiUrl = $result['wiki_url'] ?? 'Not stored';
            $this->line("\nWiki URL: {$wikiUrl}");
            
            $this->line("\nIngredients:");
            foreach ($result['ingredients'] as $ing) {
                $id = $ing['item_id'] ? "(ID: {$ing['item_id']})" : "(Not found)";
                $this->line("  - {$ing['item_name']} ×{$ing['quantity']} {$id}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->line("\nMake sure OPENAI_API_KEY is set in .env");
            return 1;
        }
    }
}

