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
use Intervention\Image\Facades\Image;

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

        $filename = $this->uploadImage($request);

        Activity::create($request->validated() + [
            'company_id' => $company->id,
            'photo' => $filename,
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

        $filename = $this->uploadImage($request);

        $activity->update($request->validated() + [
            'photo' => $filename ?? $activity->photo,
        ]);

        return to_route('companies.activities.index', $company);
    }

    public function destroy(Company $company, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $activity->delete();

        return to_route('companies.activities.index', $company);
    }

    private function uploadImage(StoreActivityRequest|UpdateActivityRequest $request)
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $filename = $request->file('image')->store(options: 'activities');

        $img = Image::make(Storage::disk('activities')->get($filename))
            ->resize(274, 274, function ($constraint) {
                $constraint->aspectRatio();
            });

        Storage::disk('activities')->put(
            'thumbs/' . $request->file('image')->hashName(),
            $img->stream()
        );

        return $filename;
    }
}
