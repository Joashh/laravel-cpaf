<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtensionResource\Pages;
use App\Filament\Resources\ExtensionResource\RelationManagers;
use App\Models\Extension;
use App\Models\Users;
use Doctrine\DBAL\Schema\Column;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;


class ExtensionResource extends Resource
{
    protected static ?string $model = Extension::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Extension Programs';

    protected static ?string $navigationLabel = 'Extension Involvements';    protected static ?int $navigationSort = 3;
    protected static ?string $pluralLabel = 'Extension Involvements';
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
        // Remove titles
        $nameWithoutTitles = str_ireplace($titles, '', $name);
        // Replace multiple spaces with a single space
        return preg_replace('/\s+/', ' ', trim($nameWithoutTitles));
    };

    // Normalize names
    $normalizedFullName = $normalizeName($fullName);
    $normalizedFullNameReversed = $normalizeName($fullNameReversed);
    $normalizedSimpleName = $normalizeName($simpleName);

    return static::$model::where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
        $query->whereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
              ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
              ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
    })->count();
}


    public static function getNavigationBadgeColor(): string
    {
        return 'secondary'; 
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Full Name')
                    ->default(Auth::user()->name. ' ' . Auth::user()->last_name) // Gets logged-in user's name
                    ->hidden()
                    ->required(),

                Select::make('extension_involvement')
                ->label('Type of Extension Involvement')
                ->options([
                    'Resource Person' => 'Resource Person',
                    'Seminar Speaker' => 'Seminar Speaker',
                    'Reviewer' => 'Reviewer',
                    'Evaluator' => 'Evaluator',
                    'Moderator' => 'Moderator',
                    'Session Chair' => 'Session Chair',
                    'Editor' => 'Editor',
                    'Examiner' => 'Examiner',
                    'Other' => 'Other (Specify)', // Adds "Other" as an option
                ])
                ->reactive(), // Allows dynamic updates based on selection
                
                Select::make('location')
                ->label('Type of Extension')
                ->options([
                    'Training' => 'Training',
                    'Conference' => 'Conference',
                    'Editorial Team/Board' => 'Editorial Team/Board',
                    'Workshop' => 'Workshop',
                    'Other' => 'Other (Specify)', // Adds "Other" as an option
                ])
                ->reactive(), // Allows dynamic updates based on selection

            TextInput::make('custom_involvement')
                ->label('Specify Other')
                ->hidden(fn ($get) => $get('type_of_involvement') !== 'Other') // Show only if "Other" is selected
                ->maxLength(255),

                TextInput::make('event_title')
                ->label("Event Title"),

                TextInput::make('venue')
                ->label("Venue and Location"),
                
                DatePicker::make('activity_date')
                ->label('Activity Date'),

            ])->columns(1);
    }  

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*TextColumn::make('contributing_unit')->label('Contributing Unit')
                ->sortable()->searchable(),
                TextColumn::make('title')->label('Title')
                ->sortable()->searchable(),
                TextColumn::make('faculty.first_name')->label("Project Leader")
                ->sortable()->searchable(),
                TextColumn::make('start_date')
                ->sortable()->searchable(),
                TextColumn::make('end_date')
                ->sortable()->searchable(),
                */
               // IconColumn::make('pbms_upload_status')
               // ->icon(fn (string $state): string => match ($state) {
                   //   'uploaded' => 'heroicon-o-check-badge',
                   //  'pending' => 'heroicon-o-clock',
                
                  //  })
                TextColumn::make('activity_date')->label('Timestamp')
                ->sortable()->searchable() ->date('F d, Y'),
                TextColumn::make('name')->label('Full Names')
                ->sortable()->searchable()
                ->limit(20) // Only show first 20 characters
                ->tooltip(fn ($state) => $state),
                TextColumn::make('extension_involvement')->label('Type of Extension Involvement')
                ->sortable()->searchable(),
                TextColumn::make('event_title')->label('Event Title')
                ->sortable()->searchable()
                ->limit(20) // Only show first 20 characters
                ->tooltip(fn ($state) => $state), // Show full name on hover,
                TextColumn::make('created_at')->label('Start Date')
                ->sortable()->searchable(),
                TextColumn::make('extensiontype')->label('Type of Extension')
                ->sortable()->searchable(),
                TextColumn::make('venue')->label('Event Venue')
                ->sortable()->searchable()
                ->limit(10) // Only show first 20 characters
                ->tooltip(fn ($state) => $state),
                TextColumn::make('date_end')->label('End Date')
                ->sortable()->searchable(),
                
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
        }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExtensions::route('/'),
            'create' => Pages\CreateExtension::route('/create'),
            'view' => Pages\ViewExtension::route('/{record}'),
            'edit' => Pages\EditExtension::route('/{record}/edit'),
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
        // Remove titles
        $nameWithoutTitles = str_ireplace($titles, '', $name);
        // Replace multiple spaces with a single space
        return preg_replace('/\s+/', ' ', trim($nameWithoutTitles));
    };

    // Normalize names
    $normalizedFullName = $normalizeName($fullName);
    $normalizedFullNameReversed = $normalizeName($fullNameReversed);
    $normalizedSimpleName = $normalizeName($simpleName);

    return parent::getEloquentQuery()
        ->where(function ($query) use ($normalizedFullName, $normalizedFullNameReversed, $normalizedSimpleName) {
            $query->whereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullName%"])
                  ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedFullNameReversed%"])
                  ->orWhereRaw("LOWER(REPLACE(name, 'Dr.', '')) LIKE LOWER(?)", ["%$normalizedSimpleName%"]);
        });
}





}
