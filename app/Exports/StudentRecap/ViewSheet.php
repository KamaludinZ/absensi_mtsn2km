<?php

namespace App\Exports\StudentRecap;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ViewSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(private string $view, private array $report, private string $title)
    {
    }

    public function view(): View
    {
        return view($this->view, $this->report);
    }

    public function title(): string
    {
        return $this->title;
    }
}
