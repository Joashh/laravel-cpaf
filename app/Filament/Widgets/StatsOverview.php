<?php
namespace App\Filament\Widgets;

use App\Models\ChapterInBook;
use App\Models\Extension;
use App\Models\ExtensionPrime;
use App\Models\Research;
use App\Models\TrainingOrganize;
use App\Models\OrganizedTraining;
use App\Models\User;
use App\Models\AwardsRecognitions;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    // Add properties to accept start and end date
    public $startDate;
    public $endDate;

    public function mount($startDate = null, $endDate = null)
    {
        // Assign passed date range or default to start of year and current date
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfYear();
        $this->endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        // Convert the dates into Carbon instances
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        \Log::info('Start Date: ' . $startDate);
        \Log::info('End Date: ' . $endDate);

        // If the user is an admin, count all records
        if ($user->hasRole(['super-admin', 'admin'])) {
            return [
                Stat::make('Total: Faculty and REPS', User::whereIn('staff', ['faculty', 'representatives'])->count())
                    ->chart([1, 3, 5])
                    ->color('success'),

                Stat::make('Total: Research Project', Research::whereBetween('start_date', [$startDate, $endDate])->count())
                    ->chart([1, 3, 5])
                    ->color('success'),

                Stat::make('Total: Extension', Extension::whereBetween('activity_date', [$startDate, $endDate])->count()) // Use activity_date for Extension
                    ->chart([1, 3, 5])
                    ->color('success'),

                Stat::make('Total: Training Organized', TrainingOrganize::whereBetween('start_date', [$startDate, $endDate])->count())
                    ->chart([1, 3, 5])
                    ->color('success'),

                Stat::make('Total: Chapter in Book', ChapterInBook::count())
                    ->chart([1, 3, 5])
                    ->color('success'),

                Stat::make('Total: Awards', AwardsRecognitions::whereBetween('date_awarded', [$startDate, $endDate])->count()) // Use date_awarded for Awards
                    ->chart([1, 3, 5])
                    ->color('secondary'),
            ];
        }

        // Build possible name formats (same logic as getEloquentQuery)
        $fullName = trim($user->name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name);
        $fullNameReversed = trim($user->last_name . ', ' . $user->name . ' ' . ($user->middle_name ?? ''));
        $simpleName = trim($user->name . ' ' . $user->last_name);

        $titles = ['Dr.', 'Prof.', 'Engr.', 'Sir', 'Ms.', 'Mr.', 'Mrs.'];

        // Function to normalize names by removing titles and extra spaces
        $normalizeName = function ($name) use ($titles) {
            // Remove titles
            $nameWithoutTitles = str_ireplace($titles, '', $name);
            // Replace multiple spaces with a single space
            return preg_replace('/\s+/', ' ', trim($nameWithoutTitles));
        };

        $normalizedFullName = $normalizeName($fullName);
        $normalizedFullNameReversed = $normalizeName($fullNameReversed);
        $normalizedSimpleName = $normalizeName($simpleName);

        return [
            Stat::make('Total: Faculty and REPS', User::whereIn('staff', ['faculty', 'representatives', 'admin'])
                ->where('id', $user->id) // Only count the logged-in user
                ->count())
                ->chart([1, 3, 5])
                ->color('primary'),

            Stat::make('Total: Research Project', Research::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
                $query->whereRaw("LOWER(REPLACE(name_of_researchers, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
                    ->orWhereRaw("LOWER(REPLACE(name_of_researchers, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                    ->orWhereRaw("LOWER(REPLACE(name_of_researchers, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
            })->whereBetween('start_date', [$startDate, $endDate])->count())
                ->chart([1, 3, 5])
                ->color('primary'),

            Stat::make('Total: Extension & Extension Involvement', Extension::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
                $query->whereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
                    ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                    ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
            })->whereBetween('activity_date', [$startDate, $endDate])->count() + ExtensionPrime::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
                $query->whereRaw("LOWER(REPLACE(project_leader, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
                    ->orWhereRaw("LOWER(REPLACE(project_leader, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                    ->orWhereRaw("LOWER(REPLACE(project_leader, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
            })->whereBetween('start_date', [$startDate, $endDate])->count())
                ->chart([1, 3, 5])
                ->color('primary'),

            Stat::make('Total: Training Organized', OrganizedTraining::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
                $query->whereRaw(
                    "LOWER(REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name), 'Dr.', '')) LIKE LOWER(?)",
                    ["%$normalizedFullName%"]
                )
                    ->orWhereRaw(
                        "LOWER(REPLACE(CONCAT_WS(' ', last_name, first_name, middle_name), 'Dr.', '')) LIKE LOWER(?)",
                        ["%$normalizedFullNameReversed%"]
                    )
                    ->orWhereRaw(
                        "LOWER(REPLACE(CONCAT_WS(' ', first_name, last_name), 'Dr.', '')) LIKE LOWER(?)",
                        ["%$normalizedSimpleName%"]
                    );
            })->whereBetween('start_date', [$startDate, $endDate])->count())
                ->chart([1, 3, 5])
                ->color('secondary'),

            Stat::make('Total: Publications', TrainingOrganize::count())
                ->chart([1, 3, 5])
                ->color('secondary'),

            Stat::make('Total: Awards', AwardsRecognitions::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
                $query->whereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
                    ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                    ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
            })->whereBetween('date_awarded', [$startDate, $endDate])->count()) // Use date_awarded for Awards
                ->chart([1, 3, 5])
                ->color('secondary'),
        ];
    }
}
