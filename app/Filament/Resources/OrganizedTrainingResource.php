<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizedTrainingResource\Pages;
use App\Models\OrganizedTraining;
use Filament\Forms;
use Filament\Forms\Components\{TextInput, Select, DatePicker, Textarea, FileUpload, Section, Grid};
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Illuminate\Database\Eloquent\Builder;

class OrganizedTrainingResource extends Resource
{
    protected static ?string $model = OrganizedTraining::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Training Programs';
    protected static ?string $navigationLabel = 'Training Organized';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
{
    $user = Auth::user();

    // If the user is an admin, show the total count
    if ($user->hasRole(['super-admin', 'admin'])) {
        return static::$model::count();
    }

    // Build possible name formats
    $fullName = trim("{$user->name} " . ($user->middle_name ? "{$user->middle_name} " : "") . "{$user->last_name}");
    $fullNameReversed = trim("{$user->last_name}, {$user->name}" . ($user->middle_name ? " {$user->middle_name}" : ""));
    $simpleName = trim("{$user->name} {$user->last_name}");

    // List of titles to remove
    $titles = ['Dr.', 'Prof.', 'Engr.', 'Sir', 'Ms.', 'Mr.', 'Mrs.'];

    // Function to normalize names by removing titles and extra spaces
    $normalizeName = function ($name) use ($titles) {
        return preg_replace('/\s+/', ' ', trim(str_ireplace($titles, '', $name)));
    };

    // Normalize names
    $normalizedFullName = $normalizeName($fullName);
    $normalizedFullNameReversed = $normalizeName($fullNameReversed);
    $normalizedSimpleName = $normalizeName($simpleName);

    return static::$model::where(function ($query) use ($user, $normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
        $query->whereRaw("LOWER(CONCAT(TRIM(first_name), ' ', TRIM(middle_name), ' ', TRIM(last_name))) LIKE LOWER(?)", ["%$normalizedFullName%"])
              ->orWhereRaw("LOWER(CONCAT(TRIM(last_name), ', ', TRIM(first_name), ' ', TRIM(middle_name))) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
              ->orWhereRaw("LOWER(CONCAT(TRIM(first_name), ' ', TRIM(last_name))) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
    })->count();
}

    public static function getNavigationBadgeColor(): string
    {
        return 'secondary'; 
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Training Details')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('first_name')->label('First Name')->required(),
                            TextInput::make('middle_name')->label('Middle Name'),
                            TextInput::make('last_name')->label('Last Name')->required(),
                        ]),
                        Select::make('contributing_unit')->label('Contributing Unit')
                            ->options([
                                'CSPPS' => 'CSPPS',
                                'CISC' => 'CISC',
                                'IGRD' => 'IGRD',
                                'CPAf' => 'CPAf',
                            ])
                            ->required(),
                        TextInput::make('title')->label('Title of the Event')->required(),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')->label('Start Date')->required(),
                            DatePicker::make('end_date')->label('End Date')->required(),
                        ]),
                        Textarea::make('special_notes')->label('Special Notes'),
                        Textarea::make('resource_persons')->label('Resource Person(s)'),
                        Select::make('activity_category')->label('Activity Category')
                            ->options([
                                'Training/Workshop' => 'Training/Workshop',
                                'Seminar/Forum/Round Table' => 'Seminar/Forum/Round Table',
                            ])
                            ->required(),
                        TextInput::make('venue')->label('Venue')->required(),
                    ]),

                Section::make('Trainee Details')
                    ->schema([
                        TextInput::make('total_trainees')->label('Total Trainees')->numeric()->required(),
                        TextInput::make('weighted_trainees')->label('Weighted Trainees')->numeric()->required(),
                        TextInput::make('training_hours')->label('Training Hours')->numeric()->required(),
                        Select::make('funding_source')->label('Funding Source')
                            ->options([
                                'UP Entity' => 'UP Entity',
                                'RP Government Entity or Public Sector Entity' => 'RP Government Entity or Public Sector Entity',
                                'RP Private Sector Entity' => 'RP Private Sector Entity',
                                'Foreign or Non-Domestic Entity' => 'Foreign or Non-Domestic Entity',
                            ])
                            ->required(),
                    ]),

                Section::make('Survey Responses')
                    ->schema([
                        TextInput::make('sample_size')->label('Sample Size')->numeric(),
                        TextInput::make('responses_poor')->label('Number of Responses - Poor/Below Fair')->numeric(),
                        TextInput::make('responses_fair')->label('Number of Responses - Fair')->numeric(),
                        TextInput::make('responses_satisfactory')->label('Number of Responses - Satisfactory')->numeric(),
                        TextInput::make('responses_very_satisfactory')->label('Number of Responses - Very Satisfactory')->numeric(),
                        TextInput::make('responses_outstanding')->label('Number of Responses - Outstanding')->numeric(),
                    ]),

                Section::make('Supporting Documents')
                    ->schema([
                        TextInput::make('related_extension_program')->label('Related Extension Program, if applicable'),
                        FileUpload::make('pdf_file_1')->label('PDF File 1')->directory('organized_trainings'),
                        FileUpload::make('pdf_file_2')->label('PDF File 2')->directory('organized_trainings'),
                        TextInput::make('documents_link')->label('Documents Link'),
                        TextInput::make('project_title')->label('Project Title'),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')->label('First Name')->searchable(),
                TextColumn::make('last_name')->label('Last Name')->searchable(),
                TextColumn::make('title')->label('Title')->searchable()
                ->limit(20) // Only show first 20 characters
                ->tooltip(fn ($state) => $state), // Show full name on hover,,
                TextColumn::make('start_date')->label('Start Date')->date('Y-m-d'),
                TextColumn::make('end_date')->label('End Date')->date('Y-m-d'),
                BadgeColumn::make('contributing_unit')->label('Contributing Unit'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizedTrainings::route('/'),
            'create' => Pages\CreateOrganizedTraining::route('/create'),
            'edit' => Pages\EditOrganizedTraining::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
{
    $user = Auth::user();

    // If the user is an admin, return all records
    if ($user->hasRole(['super-admin', 'admin'])) {
        return parent::getEloquentQuery();
    }

    // Build possible name formats
    $fullName = trim("{$user->name} " . ($user->middle_name ? "{$user->middle_name} " : "") . "{$user->last_name}");
    $fullNameReversed = trim("{$user->last_name}, {$user->name}" . ($user->middle_name ? " {$user->middle_name}" : ""));
    $simpleName = trim("{$user->name} {$user->last_name}");

    // List of titles to remove
    $titles = ['Dr.', 'Prof.', 'Engr.', 'Sir', 'Ms.', 'Mr.', 'Mrs.'];

    // Function to normalize names by removing titles and extra spaces
    $normalizeName = function ($name) use ($titles) {
        return preg_replace('/\s+/', ' ', trim(str_ireplace($titles, '', $name)));
    };

    // Normalize names
    $normalizedFullName = $normalizeName($fullName);
    $normalizedFullNameReversed = $normalizeName($fullNameReversed);
    $normalizedSimpleName = $normalizeName($simpleName);

    return parent::getEloquentQuery()
        ->where(function ($query) use ($user, $normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
            $query->whereRaw("LOWER(CONCAT(TRIM(first_name), ' ', TRIM(middle_name), ' ', TRIM(last_name))) LIKE LOWER(?)", ["%$normalizedFullName%"])
                  ->orWhereRaw("LOWER(CONCAT(TRIM(last_name), ', ', TRIM(first_name), ' ', TRIM(middle_name))) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                  ->orWhereRaw("LOWER(CONCAT(TRIM(first_name), ' ', TRIM(last_name))) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
        });
}

    
    
}
