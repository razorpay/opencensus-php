<?php

namespace RZP\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use PhpParser\ParserFactory;
use PhpParser\Node;
use Symfony\Component\Finder\Finder;

class LintBlade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:lintblade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure JS is not generated out of PHP in blade files';

    /**
     * Get all the files from the given directory (recursive) matching the name pattern.
     *
     * @param string $directory   The directory to search in.
     * @param string $namePattern The name pattern to match for.
     *
     * @return array
     */
    protected function allFiles(string $directory, string $namePattern): array
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(true)->in($directory)->name($namePattern)->sortByName(),
            false
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $libxml_previous_state = libxml_use_internal_errors(true);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        foreach ($this->allFiles('resources/views/', '*.blade.php') as $filename) {
            $contents = file_get_contents($filename);
            $phpCode = Blade::compileString($contents);
            $doc = new DOMDocument();
            $doc->loadHTML($phpCode);
            $scripts = $doc->getElementsByTagName('script');
            if ($scripts->length === 0) {
                continue;
            }
            foreach ($scripts as $node) {
                $ast = $parser->parse($node->textContent);
                foreach ($ast as $phpNode) {
                    if ($phpNode instanceof Node\Stmt\InlineHTML) {
                        continue;
                    }

                    if (($phpNode instanceof Node\Stmt\Expression) &&
                        $phpNode->expr->name->parts === ['print_jssafe_json'])
                    {
                        continue;
                    }
                    break 2;
                }
                continue 2;
            }
            echo $filename . "\n";
        }
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous_state);
    }
}
