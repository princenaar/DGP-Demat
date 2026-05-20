<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use App\Services\WorkflowTransitionTemplate;
use Illuminate\Database\Seeder;

class WorkflowTransitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workflowTemplate = app(WorkflowTransitionTemplate::class);

        TypeDocument::query()
            ->get()
            ->each(function (TypeDocument $typeDocument) use ($workflowTemplate): void {
                $workflowTemplate->createFor($typeDocument);
            });
    }
}
