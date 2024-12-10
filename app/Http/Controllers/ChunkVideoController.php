<?php

namespace App\Http\Controllers;

use App\Models\ChunkVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChunkVideoController extends Controller
{
    public function create(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:mp4,avi,zip,pdf|max:10002400',
            'fileName' => 'required|string',
            'chunkNumber' => 'required|integer',
            'totalChunks' => 'required|integer',
        ]);

        $fileName = $request->fileName;
        $chunkNumber = $request->chunkNumber;
        $totalChunks = $request->totalChunks;

        // Path to store temporary chunks
        $tempDir = storage_path('app/temp_chunks');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Save the current chunk
        $chunkPath = $tempDir . '/' . $fileName . '.part' . $chunkNumber;
        file_put_contents($chunkPath, file_get_contents($request->file('file')->getRealPath()));

        // Create video record in the database if it's the first chunk
        if ($chunkNumber == 1) {
            ChunkVideo::create([
                'file_name' => $fileName,
                'file_size' => $request->file('file')->getSize(),
                'status' => 'uploading',
            ]);
        }

        // If it's the last chunk, merge the chunks
        if ($chunkNumber == $totalChunks) {
            $completePath = storage_path('app/public/videos/' . $fileName);

            if (!is_dir(dirname($completePath))) {
                mkdir(dirname($completePath), 0777, true);
            }

            // Merge chunks
            if (!file_exists($completePath)) {
                $outputFile = fopen($completePath, 'wb');
                for ($i = 1; $i <= $totalChunks; $i++) {
                    $chunkPath = $tempDir . '/' . $fileName . '.part' . $i;
                    if (file_exists($chunkPath)) {
                        fwrite($outputFile, file_get_contents($chunkPath));
                        unlink($chunkPath); // Remove chunk after merging
                    } else {
                        return response()->json(['status' => 'error', 'message' => "Chunk {$i} missing"], 400);
                    }
                }
                fclose($outputFile);

                // Update the video record
                $video = ChunkVideo::where('file_name', $fileName)->first();
                if ($video) {
                    $video->file_path = 'videos/' . $fileName; // Ensure file_path is correctly updated
                    $video->status = 'completed';
                    $video->save();
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $video = ChunkVideo::findOrFail($id);

        // Generate the full URL for the video
        $videoUrl = asset('storage/' . $video->file_path);

        return response()->json([
            'id' => $video->id,
            'file_name' => $video->file_name,
            'file_path' => $videoUrl, // Make sure it's pointing to the correct public path
            'file_size' => $video->file_size,
            'status' => $video->status,
        ]);
    }
}
