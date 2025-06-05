<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManagerController extends Controller
{
    protected $fileManager;

    public function __construct(FileManagerService $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function index(Request $request)
    {
        $path = $request->get('path', '');
        $items = $this->fileManager->listDirectory($path);

        return view('admin.filemanager.index', compact('items', 'path'));
    }

    public function createDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|regex:/^[a-zA-Z0-9-_]+$/'
        ]);

        try {
            $this->fileManager->createDirectory($request->path . '/' . $request->name);
            return redirect()->back()->with('success', 'Directory created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function deleteDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $this->fileManager->deleteDirectory($request->path);
            return redirect()->back()->with('success', 'Directory deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'file' => 'required|file'
        ]);

        try {
            $this->fileManager->uploadFile($request->path, $request->file('file'));
            return redirect()->back()->with('success', 'File uploaded successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $this->fileManager->deleteFile($request->path);
            return redirect()->back()->with('success', 'File deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function moveFile(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'destination' => 'required|string'
        ]);

        try {
            $this->fileManager->moveFile($request->source, $request->destination);
            return redirect()->back()->with('success', 'File moved successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function copyFile(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'destination' => 'required|string'
        ]);

        try {
            $this->fileManager->copyFile($request->source, $request->destination);
            return redirect()->back()->with('success', 'File copied successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function editFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $contents = $this->fileManager->getFileContents($request->path);
            $fileInfo = $this->fileManager->getFileInfo($request->path);
            return view('admin.filemanager.edit', compact('contents', 'fileInfo'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function saveFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'contents' => 'required|string'
        ]);

        try {
            $this->fileManager->saveFileContents($request->path, $request->contents);
            return redirect()->back()->with('success', 'File saved successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function changePermissions(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'permissions' => 'required|string|regex:/^[0-7]{3,4}$/'
        ]);

        try {
            $this->fileManager->changePermissions($request->path, $request->permissions);
            return redirect()->back()->with('success', 'Permissions changed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show($path)
    {
        if (Storage::disk('public')->exists($path)) {
            if (Storage::disk('public')->exists($path) && !Storage::disk('public')->exists($path . '/')) {
                // It's a file
                $fullPath = Storage::disk('public')->path($path);
                return new StreamedResponse(function () use ($path) {
                    $stream = Storage::disk('public')->readStream($path);
                    fpassthru($stream);
                    fclose($stream);
                }, 200, [
                    'Content-Type' => mime_content_type($fullPath),
                    'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
                ]);
            } else {
                // It's a directory
                $files = collect(Storage::disk('public')->files($path))
                    ->map(function ($file) {
                        return [
                            'name' => basename($file),
                            'type' => 'file',
                            'size' => Storage::disk('public')->size($file),
                            'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($file))
                        ];
                    })
                    ->merge(collect(Storage::disk('public')->directories($path))
                        ->map(function ($dir) {
                            return [
                                'name' => basename($dir),
                                'type' => 'dir',
                                'size' => '-',
                                'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($dir))
                            ];
                        }))
                    ->sortBy('name')
                    ->values();
                return view('admin.files.index', compact('files', 'path'));
            }
        }

        abort(404);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string',
            'type' => 'required|in:file,directory',
        ]);

        $path = $request->input('path');
        $name = $request->input('name');
        $type = $request->input('type');

        if ($type === 'directory') {
            Storage::disk('public')->makeDirectory($path . '/' . $name);
        } else {
            Storage::disk('public')->put($path . '/' . $name, '');
        }

        return redirect()->back()->with('success', ucfirst($type) . ' created successfully.');
    }

    public function update(Request $request, $path)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newName = $request->input('name');
        $newPath = Str::beforeLast($path, '/') . '/' . $newName;

        Storage::disk('public')->move($path, $newPath);

        return redirect()->back()->with('success', 'File renamed successfully.');
    }

    public function destroy($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return redirect()->back()->with('success', 'File deleted successfully.');
        }

        abort(404);
    }

    public function extract(Request $request)
    {
        $request->validate([
            'archive' => 'required|string',
            'path' => 'required|string',
        ]);
        $archive = $request->input('archive');
        $path = $request->input('path');
        $fullArchivePath = Storage::disk('public')->path($archive);
        $fullPath = Storage::disk('public')->path($path);
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === 'WIN') {
            // Windows: Only zip supported
            $zip = new \ZipArchive();
            if ($zip->open($fullArchivePath) === true) {
                $zip->extractTo($fullPath);
                $zip->close();
                return redirect()->back()->with('success', 'Archive extracted successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to extract archive.');
            }
        } else {
            // Linux/mac: zip and tar
            if (preg_match('/\.zip$/i', $archive)) {
                $zip = new \ZipArchive();
                if ($zip->open($fullArchivePath) === true) {
                    $zip->extractTo($fullPath);
                    $zip->close();
                    return redirect()->back()->with('success', 'Archive extracted successfully.');
                } else {
                    return redirect()->back()->with('error', 'Failed to extract archive.');
                }
            } elseif (preg_match('/\.(tar|tar\.gz|tgz)$/i', $archive)) {
                $cmd = "tar -xf " . escapeshellarg($fullArchivePath) . " -C " . escapeshellarg($fullPath);
                exec($cmd, $output, $result);
                if ($result === 0) {
                    return redirect()->back()->with('success', 'Archive extracted successfully.');
                } else {
                    return redirect()->back()->with('error', 'Failed to extract archive.');
                }
            } else {
                return redirect()->back()->with('error', 'Unsupported archive format.');
            }
        }
    }

    public function compress(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'path' => 'required|string',
            'archive_name' => 'required|string',
        ]);
        $files = $request->input('files');
        $path = $request->input('path');
        $archiveName = $request->input('archive_name');
        $fullPath = Storage::disk('public')->path($path);
        $archivePath = $fullPath . DIRECTORY_SEPARATOR . $archiveName;
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === 'WIN') {
            // Windows: Only zip supported
            $zip = new \ZipArchive();
            if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($fullPath . DIRECTORY_SEPARATOR . $file, $file);
                }
                $zip->close();
                return redirect()->back()->with('success', 'Archive created successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to create archive.');
            }
        } else {
            // Linux/mac: zip and tar
            if (preg_match('/\.zip$/i', $archiveName)) {
                $zip = new \ZipArchive();
                if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    foreach ($files as $file) {
                        $zip->addFile($fullPath . DIRECTORY_SEPARATOR . $file, $file);
                    }
                    $zip->close();
                    return redirect()->back()->with('success', 'Archive created successfully.');
                } else {
                    return redirect()->back()->with('error', 'Failed to create archive.');
                }
            } elseif (preg_match('/\.(tar|tar\.gz|tgz)$/i', $archiveName)) {
                $fileList = implode(' ', array_map('escapeshellarg', $files));
                $cmd = "tar -czf " . escapeshellarg($archivePath) . " -C " . escapeshellarg($fullPath) . " " . $fileList;
                exec($cmd, $output, $result);
                if ($result === 0) {
                    return redirect()->back()->with('success', 'Archive created successfully.');
                } else {
                    return redirect()->back()->with('error', 'Failed to create archive.');
                }
            } else {
                return redirect()->back()->with('error', 'Unsupported archive format.');
            }
        }
    }

    public function permissions(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
            'mode' => 'required|string',
        ]);
        $file = $request->input('file');
        $mode = $request->input('mode');
        $fullPath = Storage::disk('public')->path($file);
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === 'WIN') {
            // Windows: Use icacls
            $cmd = "icacls " . escapeshellarg($fullPath) . " /grant Everyone:{$mode}";
            exec($cmd, $output, $result);
            if ($result === 0) {
                return redirect()->back()->with('success', 'Permissions updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to update permissions.');
            }
        } else {
            // Linux/mac: Use chmod
            $cmd = "chmod {$mode} " . escapeshellarg($fullPath);
            exec($cmd, $output, $result);
            if ($result === 0) {
                return redirect()->back()->with('success', 'Permissions updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to update permissions.');
            }
        }
    }
} 