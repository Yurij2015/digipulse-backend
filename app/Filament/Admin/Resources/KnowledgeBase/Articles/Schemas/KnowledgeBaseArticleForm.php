<?php

namespace App\Filament\Admin\Resources\KnowledgeBase\Articles\Schemas;

use App\Models\KnowledgeBaseCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class KnowledgeBaseArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('knowledge_base_category_id')
                            ->label('Category')
                            ->options(KnowledgeBaseCategory::orderBy('sort_order')->get()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Tabs::make('Translations')
                            ->columnSpanFull()
                            ->tabs([
                                Tab::make('EN')
                                    ->schema([
                                        TextInput::make('title.en')
                                            ->label('Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                                        Textarea::make('excerpt.en')
                                            ->label('Excerpt')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->helperText('Short description shown in listings.'),

                                        RichEditor::make('content.en')
                                            ->label('Content')
                                            ->required()
                                            ->fileAttachmentsDisk('minio')
                                            ->fileAttachmentsDirectory('knowledge-base/attachments')
                                            ->toolbarButtons([
                                                'attachFiles', 'blockquote', 'bold', 'bulletList',
                                                'codeBlock', 'h2', 'h3', 'italic', 'link',
                                                'orderedList', 'redo', 'strike', 'underline', 'undo',
                                            ]),
                                    ]),

                                Tab::make('UK')
                                    ->schema([
                                        TextInput::make('title.uk')
                                            ->label('Title')
                                            ->maxLength(255),

                                        Textarea::make('excerpt.uk')
                                            ->label('Excerpt')
                                            ->rows(2)
                                            ->maxLength(500),

                                        RichEditor::make('content.uk')
                                            ->label('Content')
                                            ->fileAttachmentsDisk('minio')
                                            ->fileAttachmentsDirectory('knowledge-base/attachments')
                                            ->toolbarButtons([
                                                'attachFiles', 'blockquote', 'bold', 'bulletList',
                                                'codeBlock', 'h2', 'h3', 'italic', 'link',
                                                'orderedList', 'redo', 'strike', 'underline', 'undo',
                                            ]),
                                    ]),

                                Tab::make('PL')
                                    ->schema([
                                        TextInput::make('title.pl')
                                            ->label('Title')
                                            ->maxLength(255),

                                        Textarea::make('excerpt.pl')
                                            ->label('Excerpt')
                                            ->rows(2)
                                            ->maxLength(500),

                                        RichEditor::make('content.pl')
                                            ->label('Content')
                                            ->fileAttachmentsDisk('minio')
                                            ->fileAttachmentsDirectory('knowledge-base/attachments')
                                            ->toolbarButtons([
                                                'attachFiles', 'blockquote', 'bold', 'bulletList',
                                                'codeBlock', 'h2', 'h3', 'italic', 'link',
                                                'orderedList', 'redo', 'strike', 'underline', 'undo',
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columns(2),

                Section::make('SEO & Publishing')
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->image()
                            ->disk('minio')
                            ->visibility('public')
                            ->directory('knowledge-base/covers')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->maxSize(2048)
                            ->helperText('Used as OG image. 16:9, max 2MB.'),

                        TextInput::make('meta_title')
                            ->maxLength(60)
                            ->helperText('Leave blank to use article title.'),

                        Textarea::make('meta_description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Max 160 characters.'),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        DateTimePicker::make('published_at')
                            ->label('Publish At')
                            ->helperText('Leave blank to save as draft.'),
                    ]),
            ])
            ->columns(3);
    }
}
