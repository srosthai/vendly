<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lists\TestimonialListRequest;
use App\Http\Requests\SaveTestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Quotes from real people, shown on the website once published. Nothing on
 * the Testimonials page comes from anywhere else.
 */
class TestimonialController extends Controller
{
    public function index(TestimonialListRequest $request): Response
    {
        $filters = $request->filters();
        $pattern = $request->searchPattern();

        return Inertia::render('admin/testimonials', [
            'filters' => $filters,
            'testimonials' => Testimonial::query()
                ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->whereLike('name', $pattern)
                    ->orWhereLike('role', $pattern)
                    ->orWhereLike('quote', $pattern)))
                ->when($filters['status'] === 'published', fn ($query) => $query->whereNotNull('published_at'))
                ->when($filters['status'] === 'draft', fn ($query) => $query->whereNull('published_at'))
                ->orderBy('sort')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(TestimonialListRequest::PerPage)
                ->withQueryString()
                ->through(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'name' => $testimonial->name,
                    'role' => $testimonial->role,
                    'quote' => $testimonial->quote,
                    'sort' => $testimonial->sort,
                    'published' => $testimonial->published_at !== null,
                ]),
        ]);
    }

    public function store(SaveTestimonialRequest $request): RedirectResponse
    {
        $testimonial = new Testimonial;
        $this->fill($testimonial, $request);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonial added.']);

        return back();
    }

    public function update(SaveTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->fill($testimonial, $request);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonial saved.']);

        return back();
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonial deleted.']);

        return back();
    }

    private function fill(Testimonial $testimonial, SaveTestimonialRequest $request): void
    {
        $validated = $request->validated();

        $testimonial->fill([
            'name' => strip_tags($validated['name']),
            'role' => isset($validated['role']) ? strip_tags($validated['role']) : null,
            'quote' => strip_tags($validated['quote']),
            'sort' => (int) ($validated['sort'] ?? 0),
            'published_at' => $request->boolean('published') ? ($testimonial->published_at ?? now()) : null,
        ])->save();
    }
}
