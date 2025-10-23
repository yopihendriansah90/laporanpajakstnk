<?php

namespace App\Filament\Resources\PengajuanResource\Pages;

use App\Filament\Resources\PengajuanResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPengajuan extends EditRecord
{
    protected static string $resource = PengajuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record?->status === 'draft'),

            Actions\Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn () => $this->record?->status === 'draft')
                ->action(function (): void {
                    if ($this->record->items()->count() < 1) {
                        Notification::make()
                            ->title('Minimal 1 item diperlukan sebelum submit.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->markSubmitted('Submit via Filament');
                    Notification::make()
                        ->title('Pengajuan berhasil diajukan.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->visible(fn () => $this->record?->status === 'diajukan')
                ->action(function (): void {
                    $this->record->markApproved('Approve via Filament');

                    Notification::make()
                        ->title('Pengajuan disetujui.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn () => $this->record?->status === 'diajukan')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Alasan Penolakan')
                        ->maxLength(1000)
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->markRejected($data['message'] ?? null);

                    Notification::make()
                        ->title('Pengajuan ditolak.')
                        ->danger()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('mark_paid')
                ->label('Mark Paid')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->requiresConfirmation()
                ->visible(fn () => $this->record?->status === 'disetujui')
                ->action(function (): void {
                    $this->record->markPaid('Mark paid via Filament');

                    Notification::make()
                        ->title('Pengajuan ditandai dibayar.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Export PDF Pengajuan')
                ->form([
                    Forms\Components\TextInput::make('div_dept_cc')
                        ->label('Div/ Dept/ CC')
                        ->required(),
                    Forms\Components\TextInput::make('keperluan')
                        ->label('Keperluan')
                        ->required(),
                    Forms\Components\Repeater::make('signatories')
                        ->label('Penandatangan')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama')
                                ->required(),
                        ])
                        ->columns(1)
                        ->minItems(1)
                        ->maxItems(5),
                ])
                ->action(function (array $data): void {
                    $baseUrl = route('pengajuan.pdf', ['pengajuan' => $this->record]);

                    $names = array_values(array_filter(array_map(
                        fn ($row) => trim((string) ($row['name'] ?? '')),
                        $data['signatories'] ?? []
                    )));

                    $params = [
                        'div_dept_cc' => $data['div_dept_cc'] ?? '',
                        'keperluan' => $data['keperluan'] ?? '',
                    ];

                    foreach ($names as $i => $name) {
                        $params["signatories[$i]"] = $name;
                    }

                    $url = $baseUrl . '?' . http_build_query($params);
                    $this->redirect($url);
                }),
        ];
    }
}