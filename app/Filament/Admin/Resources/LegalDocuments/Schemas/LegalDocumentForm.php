<?php

namespace App\Filament\Admin\Resources\LegalDocuments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LegalDocumentForm
{
    private static array $toolbar = [
        'attachFiles', 'blockquote', 'bold', 'bulletList',
        'codeBlock', 'h2', 'h3', 'italic', 'link',
        'orderedList', 'redo', 'strike', 'underline', 'undo',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('published_at')
                    ->label('Publish At')
                    ->helperText('Leave blank to keep as draft (hidden from public API).'),

                Tabs::make('Translations')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('English')
                            ->schema([
                                TextInput::make('title.en')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255),
                                RichEditor::make('content.en')
                                    ->label('Content')
                                    ->required()
                                    ->fileAttachmentsDisk('minio')
                                    ->fileAttachmentsDirectory('legal/attachments')
                                    ->toolbarButtons(self::$toolbar),
                            ]),

                        Tab::make('Ukrainian')
                            ->schema([
                                TextInput::make('title.uk')
                                    ->label('Title')
                                    ->maxLength(255),
                                RichEditor::make('content.uk')
                                    ->label('Content')
                                    ->fileAttachmentsDisk('minio')
                                    ->fileAttachmentsDirectory('legal/attachments')
                                    ->toolbarButtons(self::$toolbar),
                            ]),

                        Tab::make('Polish')
                            ->schema([
                                TextInput::make('title.pl')
                                    ->label('Title')
                                    ->maxLength(255),
                                RichEditor::make('content.pl')
                                    ->label('Content')
                                    ->fileAttachmentsDisk('minio')
                                    ->fileAttachmentsDirectory('legal/attachments')
                                    ->toolbarButtons(self::$toolbar),
                            ]),
                    ]),
            ]);
    }
}
