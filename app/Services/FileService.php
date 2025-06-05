<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\File as FileModel;
use Carbon\Carbon;

class FileService
{
    protected $isWindows;
    protected $filePath;
    protected $backupPath;
    protected $tempPath;
    protected $allowedExtensions;
    protected $maxFileSize;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->filePath = $this->isWindows ? 'C:\\laragon\\www' : '/var/www';
        $this->backupPath = storage_path('backups/files');
        $this->tempPath = storage_path('temp/files');
        $this->allowedExtensions = config('files.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
        $this->maxFileSize = config('files.max_size', 10485760); // 10MB

        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }

        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    public function uploadFile(array $data): array
    {
        try {
            if (!isset($data['file'])) {
                throw new \Exception('No file provided');
            }

            $file = $data['file'];
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();

            if (!in_array($extension, $this->allowedExtensions)) {
                throw new \Exception('File type not allowed');
            }

            if ($size > $this->maxFileSize) {
                throw new \Exception('File size exceeds limit');
            }

            $filename = Str::random(40) . '.' . $extension;
            $path = $file->storeAs('files', $filename);

            $fileModel = FileModel::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $size,
                'type' => $file->getMimeType(),
                'extension' => $extension,
                'user_id' => $data['user_id'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => $fileModel
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upload file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ];
        }
    }

    public function updateFile(FileModel $file, array $data): array
    {
        try {
            $file->update($data);

            return [
                'success' => true,
                'message' => 'File updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update file: ' . $e->getMessage()
            ];
        }
    }

    public function deleteFile(FileModel $file): array
    {
        try {
            if (Storage::exists($file->path)) {
                Storage::delete($file->path);
            }

            $file->delete();

            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ];
        }
    }

    public function downloadFile(FileModel $file): array
    {
        try {
            if (!Storage::exists($file->path)) {
                throw new \Exception('File not found');
            }

            return [
                'success' => true,
                'path' => Storage::path($file->path),
                'name' => $file->name
            ];
        } catch (\Exception $e) {
            Log::error('Failed to download file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ];
        }
    }

    public function getFileInfo(FileModel $file): array
    {
        try {
            if (!Storage::exists($file->path)) {
                throw new \Exception('File not found');
            }

            return [
                'success' => true,
                'info' => [
                    'name' => $file->name,
                    'path' => $file->path,
                    'size' => $file->size,
                    'type' => $file->type,
                    'extension' => $file->extension,
                    'created_at' => $file->created_at,
                    'updated_at' => $file->updated_at
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get file info: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get file info: ' . $e->getMessage()
            ];
        }
    }

    public function getFileStats(): array
    {
        try {
            return [
                'success' => true,
                'stats' => [
                    'total' => FileModel::count(),
                    'total_size' => FileModel::sum('size'),
                    'by_type' => FileModel::selectRaw('type, count(*) as count, sum(size) as total_size')
                        ->groupBy('type')
                        ->get()
                        ->toArray(),
                    'by_extension' => FileModel::selectRaw('extension, count(*) as count, sum(size) as total_size')
                        ->groupBy('extension')
                        ->get()
                        ->toArray()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get file stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get file stats: ' . $e->getMessage()
            ];
        }
    }

    public function searchFiles(array $filters = []): array
    {
        try {
            $query = FileModel::query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['extension'])) {
                $query->where('extension', $filters['extension']);
            }

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $files = $query->orderBy('created_at', 'desc')->paginate(20);

            return [
                'success' => true,
                'files' => $files
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search files: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search files: ' . $e->getMessage()
            ];
        }
    }

    public function backupFile(FileModel $file): array
    {
        try {
            if (!Storage::exists($file->path)) {
                throw new \Exception('File not found');
            }

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupPath = $this->backupPath . '/' . $file->name . '_' . $timestamp;

            if ($this->isWindows) {
                File::copy(Storage::path($file->path), $backupPath);
            } else {
                File::copy(Storage::path($file->path), $backupPath);
            }

            return [
                'success' => true,
                'message' => 'File backed up successfully',
                'path' => $backupPath
            ];
        } catch (\Exception $e) {
            Log::error('Failed to backup file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to backup file: ' . $e->getMessage()
            ];
        }
    }

    public function restoreFile(FileModel $file, string $backupPath): array
    {
        try {
            if (!File::exists($backupPath)) {
                throw new \Exception('Backup file not found');
            }

            if ($this->isWindows) {
                File::copy($backupPath, Storage::path($file->path));
            } else {
                File::copy($backupPath, Storage::path($file->path));
            }

            return [
                'success' => true,
                'message' => 'File restored successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to restore file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore file: ' . $e->getMessage()
            ];
        }
    }
} 