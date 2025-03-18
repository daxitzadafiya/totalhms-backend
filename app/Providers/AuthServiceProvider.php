<?php

namespace App\Providers;

use App\Http\Controllers\AbsenceReasonController;
use App\Models\Absence;
use App\Models\AbsenceReason;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactPerson;
use App\Models\Department;
use App\Models\Deviation;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeRelation;
use App\Models\Goal;
use App\Models\Industry;
use App\Models\Instruction;
use App\Models\InstructionActivity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Report;
use App\Models\RiskAnalysis;
use App\Models\RiskElementSource;
use App\Models\Role;
use App\Models\Routine;
use App\Models\Task;
use App\Policies\AbsencePolicy;
use App\Policies\AbsenceReasonPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ChecklistPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPersonPolicy;
use App\Policies\ContactPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DeviationPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EmployeeRelationPolicy;
use App\Policies\GoalPolicy;
use App\Policies\IndustryPolicy;
use App\Policies\InstructionActivityPolicy;
use App\Policies\InstructionPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReportPolicy;
use App\Policies\RiskAnalysisPolicy;
use App\Policies\RiskElementSourcePolicy;
use App\Policies\RolePolicy;
use App\Policies\RoutinePolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Goal::class => GoalPolicy::class,
        Category::class => CategoryPolicy::class,
        Checklist::class => ChecklistPolicy::class,
        Company::class => CompanyPolicy::class,
        Contact::class => ContactPolicy::class,
        Department::class => DepartmentPolicy::class,
        Deviation::class => DeviationPolicy::class,
        Document::class => DocumentPolicy::class,
        Employee::class => EmployeePolicy::class,
        Instruction::class => InstructionPolicy::class,
        Project::class => ProjectPolicy::class,
        Routine::class => RoutinePolicy::class,
        Task::class => TaskPolicy::class,
        Role::class => RolePolicy::class,
        EmployeeRelation::class => EmployeeRelationPolicy::class,
        Industry::class => IndustryPolicy::class,
        InstructionActivity::class => InstructionActivityPolicy::class,
        Report::class => ReportPolicy::class,
        ContactPerson::class => ContactPersonPolicy::class,
        Permission::class => PermissionPolicy::class,
        RiskAnalysis::class => RiskAnalysisPolicy::class,
        RiskElementSource::class => RiskElementSourcePolicy::class,
        Absence::class => AbsencePolicy::class,
        AbsenceReason::class => AbsenceReasonPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
