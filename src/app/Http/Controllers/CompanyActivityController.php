<?php

namespace App\Http\Controllers;


use App\Enums\Role;
use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyActivityController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorize('viewAny', $company);

        $company->load('activities');

        return view('companies.activities.index', compact('company'));
    }

    public function create(Company $company): View
    {
        $this->authorize('create', $company);

        $guides = User::where('company_id', $company->id)
            ->where('role_id', Role::GUIDE->value)
            ->pluck('name', 'id');

        return view('companies.activities.create', compact('guides', 'company'));
    }

    public function store(StoreActivityRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('create', $company);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activities', 'public');
        }

        Activity::create($request->validated() + [
            'company_id' => $company->id,
            'photo' => $path ?? null
        ]);

        return to_route('companies.activities.index', $company);
    }

    public function edit(Company $company, Activity $activity): View
    {
        $this->authorize('update', $activity);

        $guides = User::where('company_id', $company->id)
            ->where('role_id', Role::GUIDE->value)
            ->pluck('name', 'id');

        return view('companies.activities.edit', compact('guides', 'company', 'activity'));
    }

    public function update(UpdateActivityRequest $request, Company $company, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('activities', 'public');
            if ($activity->photo) {
                Storage::disk('public')->delete($activity->photo);
            }
        }

        $activity->update($request->validated() + [
            'photo' => $path ?? $activity->photo,
        ]);

        return to_route('companies.activities.index', $company);
    }

    public function destroy(Company $company, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $activity->delete();

        return to_route('companies.activities.index', $company);
    }
}
