<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** <x-app-layout>: the signed-in shell (sidebar, top bar) around a page. */
class AppLayout extends Component
{
    public function render(): View
    {
        return view('layouts.app');
    }
}
