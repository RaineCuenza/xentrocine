<?php

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;

class UpdateMovieMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:update-metrics {--movie-id= : Update metrics for a specific movie}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update attendance, view count, and popularity metrics for all movies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $movieId = $this->option('movie-id');

        if ($movieId) {
            $movie = Movie::find($movieId);
            if (!$movie) {
                $this->error("Movie with ID '{$movieId}' not found.");
                return 1;
            }
            $movies = collect([$movie]);
            $this->info("Updating metrics for movie: {$movie->title}");
        } else {
            $movies = Movie::all();
            $this->info("Updating metrics for " . $movies->count() . " movies...");
        }

        $progressBar = $this->output->createProgressBar($movies->count());
        $progressBar->start();

        foreach ($movies as $movie) {
            $movie->updateMetrics();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Movie metrics updated successfully!');

        return 0;
    }
}
