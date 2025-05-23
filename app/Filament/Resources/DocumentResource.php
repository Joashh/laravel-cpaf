<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use League\Csv\Writer;
use Illuminate\Support\Str;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationGroup = 'Other Documents';
    protected static ?string $navigationLabel = 'MOU and MOA';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $label = 'MOU and MOA';

    public static function getNavigationBadge(): ?string
    {
        return Document::count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'primary';
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('contributing_unit')
                ->options([
                    'CSPPS' => 'CSPPS',
                    'CISC' => 'CISC',
                    'IGRD' => 'IGRD',
                    'CPAf' => 'CPAf',
                ])
                ->required(),

            Select::make('partnership_type')
                ->options([
                    'Memorandum of Agreement (MOA)' => 'Memorandum of Agreement (MOA)',
                    'Memorandum of Understanding (MOU)' => 'Memorandum of Understanding (MOU)',
                    'Others' => 'Others',
                ])
                ->required()
                ->label('Type of Partnership Agreement')
                ->reactive(),
                
            TextInput::make('partnership_type_other')
                ->label('Specify Other Partnership Type')
                ->visible(fn ($get) => $get('partnership_type') === 'Others')
                ->required(fn ($get) => $get('partnership_type') === 'Others'),

            TextInput::make('extension_title')->required(),
            TextInput::make('partner_stakeholder')->required(),
            DatePicker::make('start_date')->required(),
            DatePicker::make('end_date')->required(),

            Select::make('training_courses')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Training Courses (non-degree and non-degree)'),

            Select::make('technical_advisory_service')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Technical/Advisory Service for external clients'),

            Select::make('information_dissemination')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Information Dissemination/Communication through mass media'),

            Select::make('consultancy')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Consultancy for external clients'),

            Select::make('community_outreach')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Community Outreach or Public Service'),

            Select::make('technology_transfer')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Technology or Knowledge Transfer to Target user/adopters in industry or the community'),

            Select::make('organizing_events')
                ->options(['Yes' => 'Yes', 'No' => 'No'])
                ->label('Organizing such as symposium, forum, exhibit, performance, conference'),

            Textarea::make('scope_of_work')->nullable(),
            TextInput::make('documents_file_path')
                ->label('Documents File URL')
                ->placeholder('https://drive.google.com/...')
                ->url()
                ->maxLength(500),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('contributing_unit')->sortable()->label('Contributing Unit')->searchable(),
                BadgeColumn::make('partnership_type')
                    ->label('Partnership Type')
                    ->sortable(),

                TextColumn::make('extension_title')
                    ->searchable()
                    ->label('Extension Title')
                    ->tooltip(fn ($state) => strlen($state) > 30 ? $state : null)
                    ->formatStateUsing(fn ($state) => strlen($state) > 30 ? substr($state, 0, 30) . '...' : $state),
                TextColumn::make('partner_stakeholder')->label('Partner Stakeholder')->searchable(),
                TextColumn::make('start_date')->date('Y-m-d')->sortable(),
                TextColumn::make('end_date')->date('Y-m-d')->sortable(),
                
                BadgeColumn::make('training_courses')
                    ->label('Training Courses')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('technical_advisory_service')
                    ->label('Technical/Advisory Service')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('information_dissemination')
                    ->label('Info Dissemination')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('consultancy')
                    ->label('Consultancy')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('community_outreach')
                    ->label('Community Outreach')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('technology_transfer')
                    ->label('Technology Transfer')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                BadgeColumn::make('organizing_events')
                    ->label('Organizing Events')
                    ->alignCenter()
                    ->color(fn ($state) => match (strtolower(trim($state))) {
                        'yes' => 'success',
                        'no' => 'warning',
                        default => 'secondary',
                    }),

                
                TextColumn::make('documents_file_path')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => $state ? '🔗 View File' : 'None')
                    ->url(fn ($record) => $record->documents_file_path)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('partnership_type')
                    ->options([
                        'Memorandum of Agreement (MOA)' => 'MOA',
                        'Memorandum of Understanding (MOU)' => 'MOU',
                        'Others' => 'Others',
                    ]),
                 Tables\Filters\Filter::make('start_date_range')
                    ->form([
                        DatePicker::make('start_from')->label('Start Date From'),
                        DatePicker::make('start_until')->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_from'], fn ($q) => $q->whereDate('start_date', '>=', $data['start_from']))
                            ->when($data['start_until'], fn ($q) => $q->whereDate('start_date', '<=', $data['start_until']));
                    })
                    ->label('Start Date Range'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('exportBulk')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Select::make('type')
                            ->label('Document Type')
                            ->options([
                                'ALL' => 'All Selected',
                                'MOA' => 'MOA',
                                'MOU' => 'MOU',
                            ])
                            ->default('ALL'),
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'csv' => 'CSV',
                                'pdf' => 'PDF',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, $records) {
                        if ($data['type'] === 'MOA') {
                            $records = $records->where('partnership_type', 'Memorandum of Agreement (MOA)');
                        } elseif ($data['type'] === 'MOU') {
                            $records = $records->where('partnership_type', 'Memorandum of Understanding (MOU)');
                        }

                        return static::exportData($records, $data['format'], $data['type']);
                    }),
            ]);
    }

    public static function exportData($records, $format, $type)
    {
        if ($format === 'csv') {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());

            $csv->insertOne([
                'Unit', 'Type', 'Title', 'Partner',
                'Start Date', 'End Date', 'Training',
                'Tech Service', 'Info Dissemination',
                'Consultancy', 'Community Outreach',
                'Tech Transfer', 'Organizing Events',
                'Scope of Work', 'Document File Path'
            ]);

            foreach ($records as $record) {
                $csv->insertOne([
                    $record->contributing_unit,
                    str_contains($record->partnership_type, 'MOA') ? 'MOA' : 'MOU',
                    $record->extension_title,
                    $record->partner_stakeholder,
                    $record->start_date,
                    $record->end_date,
                    $record->training_courses,
                    $record->technical_advisory_service,
                    $record->information_dissemination,
                    $record->consultancy,
                    $record->community_outreach,
                    $record->technology_transfer,
                    $record->organizing_events,
                    $record->scope_of_work,
                    $record->documents_file_path,
                ]);
            }

            return response()->streamDownload(function () use ($csv) {
                echo $csv->toString();
            }, 'documents_export_' . now()->format('Ymd_His') . '.csv');
        }

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.documents', [
                'documents' => $records,
                'type' => $type,
            ]);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, 'documents_export_' . now()->format('Ymd_His') . '.pdf');
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
            'view' => Pages\ViewDocument::route('/{record}'),
        ];
    }
}
