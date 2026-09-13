<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\DesignRequestPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DesignRequestPlayerController extends Controller
{
    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];

    public function store(Request $request, DesignRequest $designRequest)
    {
        $this->authorizeOwner($designRequest);

        $validated = $this->validated($request, $designRequest);

        $designRequest->players()->create($validated);

        return redirect()->back()->with('success', 'Player added to the roster.');
    }

    public function update(Request $request, DesignRequestPlayer $player)
    {
        $designRequest = $player->designRequest;
        $this->authorizeOwner($designRequest);

        $validated = $this->validated($request, $designRequest, $player);

        $player->update($validated);

        return redirect()->back()->with('success', 'Player updated.');
    }

    public function destroy(DesignRequestPlayer $player)
    {
        $this->authorizeOwner($player->designRequest);

        $player->delete();

        return redirect()->back()->with('success', 'Player removed from the roster.');
    }

    /**
     * A blank CSV template the client can fill in (in Excel, Google Sheets,
     * etc.) and upload back via import(). Plain CSV — no extra dependency,
     * and it opens/saves fine as an Excel file for the client either way.
     */
    public function template(DesignRequest $designRequest)
    {
        $this->authorizeOwner($designRequest);

        $csv = "Name,Number,Position,Size\n";
        $csv .= "Juan Dela Cruz,23,Point Guard,L\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="roster-template.csv"',
        ]);
    }

    public function import(Request $request, DesignRequest $designRequest)
    {
        $this->authorizeOwner($designRequest);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()->back()->withErrors(['file' => 'Could not read that file.']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return redirect()->back()->withErrors(['file' => 'The file is empty.']);
        }

        $columns = array_map(fn($h) => strtolower(trim((string) $h)), $header);
        $nameIdx = array_search('name', $columns, true);
        $numberIdx = array_search('number', $columns, true);
        $positionIdx = array_search('position', $columns, true);
        $sizeIdx = array_search('size', $columns, true);

        if ($nameIdx === false) {
            fclose($handle);
            return redirect()->back()->withErrors(['file' => 'The file needs a "Name" column — download the template to see the expected format.']);
        }

        $existingNumbers = $designRequest->players()->whereNotNull('number')->pluck('number')->all();
        $seenNumbers = [];
        $rows = [];
        $errors = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;

            $isBlankRow = count(array_filter($data, fn($v) => trim((string) $v) !== '')) === 0;
            if ($isBlankRow) {
                continue;
            }

            $name = trim((string) ($data[$nameIdx] ?? ''));
            $number = $numberIdx !== false ? trim((string) ($data[$numberIdx] ?? '')) : '';
            $position = $positionIdx !== false ? trim((string) ($data[$positionIdx] ?? '')) : '';
            $size = $sizeIdx !== false ? strtoupper(trim((string) ($data[$sizeIdx] ?? ''))) : '';

            if ($name === '') {
                $errors[] = "Row {$lineNumber}: name is required.";
                continue;
            }

            if ($number !== '') {
                if (! preg_match('/^[0-9]{1,3}$/', $number)) {
                    $errors[] = "Row {$lineNumber}: number \"{$number}\" must be 1-3 digits.";
                    continue;
                }
                if (in_array($number, $existingNumbers, true) || in_array($number, $seenNumbers, true)) {
                    $errors[] = "Row {$lineNumber}: number {$number} is already taken.";
                    continue;
                }
                $seenNumbers[] = $number;
            }

            if ($size !== '' && ! in_array($size, self::SIZES, true)) {
                $errors[] = "Row {$lineNumber}: size \"{$size}\" is not one of " . implode(', ', self::SIZES) . '.';
                continue;
            }

            $rows[] = [
                'design_request_id' => $designRequest->id,
                'name' => $name,
                'number' => $number !== '' ? $number : null,
                'position' => $position !== '' ? $position : null,
                'size' => $size !== '' ? $size : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        if ($errors) {
            $shown = array_slice($errors, 0, 5);
            $message = implode(' ', $shown);
            if (count($errors) > 5) {
                $message .= ' (+' . (count($errors) - 5) . ' more errors)';
            }

            return redirect()->back()->withErrors(['file' => $message]);
        }

        if (! $rows) {
            return redirect()->back()->withErrors(['file' => 'No player rows found in that file.']);
        }

        if (count($rows) > 100) {
            return redirect()->back()->withErrors(['file' => 'Please import 100 players or fewer at a time.']);
        }

        DesignRequestPlayer::insert($rows);

        return redirect()->back()->with('success', count($rows) . ' player(s) imported.');
    }

    private function validated(Request $request, DesignRequest $designRequest, ?DesignRequestPlayer $player = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number' => [
                'nullable',
                'string',
                'max:3',
                'regex:/^[0-9]+$/',
                Rule::unique('design_request_players', 'number')
                    ->where('design_request_id', $designRequest->id)
                    ->ignore($player?->id),
            ],
            'position' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', Rule::in(self::SIZES)],
        ]);
    }

    private function authorizeOwner(DesignRequest $designRequest): void
    {
        if ($designRequest->user_id !== Auth::id()) {
            throw new HttpException(403, 'This is not your design request.');
        }
    }
}
