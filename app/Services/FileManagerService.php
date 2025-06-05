<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class FileManagerService
{
    protected $basePath;
    protected $allowedExtensions;
    protected $maxFileSize;

    public function __construct()
    {
        $this->basePath = storage_path('app/public');
        $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
    }

    public function listDirectory($path = '')
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Directory not found');
        }

        $items = [];
        $files = scandir($fullPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $itemPath = $fullPath . '/' . $file;
            $items[] = [
                'name' => $file,
                'path' => $path . '/' . $file,
                'type' => is_dir($itemPath) ? 'directory' : 'file',
                'size' => is_file($itemPath) ? filesize($itemPath) : null,
                'modified' => filemtime($itemPath),
                'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4),
            ];
        }

        return $items;
    }

    public function createDirectory($path)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (file_exists($fullPath)) {
            throw new \Exception('Directory already exists');
        }

        if (!mkdir($fullPath, 0755, true)) {
            throw new \Exception('Failed to create directory');
        }

        return true;
    }

    public function deleteDirectory($path)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Directory not found');
        }

        $process = new Process(['rm', '-rf', $fullPath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed to delete directory');
        }

        return true;
    }

    public function uploadFile($path, $file)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Directory not found');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception('File type not allowed');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('File size exceeds limit');
        }

        $filename = Str::random(40) . '.' . $extension;
        $file->move($fullPath, $filename);

        return [
            'name' => $filename,
            'path' => $path . '/' . $filename,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
        ];
    }

    public function deleteFile($path)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        if (!unlink($fullPath)) {
            throw new \Exception('Failed to delete file');
        }

        return true;
    }

    public function moveFile($source, $destination)
    {
        $sourcePath = $this->basePath . '/' . $source;
        $destPath = $this->basePath . '/' . $destination;
        
        if (!file_exists($sourcePath)) {
            throw new \Exception('Source file not found');
        }

        if (!rename($sourcePath, $destPath)) {
            throw new \Exception('Failed to move file');
        }

        return true;
    }

    public function copyFile($source, $destination)
    {
        $sourcePath = $this->basePath . '/' . $source;
        $destPath = $this->basePath . '/' . $destination;
        
        if (!file_exists($sourcePath)) {
            throw new \Exception('Source file not found');
        }

        if (!copy($sourcePath, $destPath)) {
            throw new \Exception('Failed to copy file');
        }

        return true;
    }

    public function getFileContents($path)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        return file_get_contents($fullPath);
    }

    public function saveFileContents($path, $contents)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        if (file_put_contents($fullPath, $contents) === false) {
            throw new \Exception('Failed to save file');
        }

        return true;
    }

    public function getFileInfo($path)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        return [
            'name' => basename($path),
            'path' => $path,
            'size' => filesize($fullPath),
            'type' => mime_content_type($fullPath),
            'modified' => filemtime($fullPath),
            'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4),
        ];
    }

    public function changePermissions($path, $permissions)
    {
        $fullPath = $this->basePath . '/' . $path;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        if (!chmod($fullPath, octdec($permissions))) {
            throw new \Exception('Failed to change permissions');
        }

        return true;
    }
} 