<?php

namespace App\Traits;

trait Paginable
{
    /**
     * Get pagination options
     * 
     * @return array
     */
    protected function getPaginationOptions(): array
    {
        return [10, 20, 30, 50, 100];
    }

    /**
     * Get the current pagination preference from request or session
     * 
     * @param string $default
     * @return int|string
     */
    protected function getPerPagePreference(string $default = '20')
    {
        // Check if per_page is set in request
        $perPage = request()->get('per_page', session('per_page', $default));
        
        // Validate against allowed options
        $validOptions = $this->getPaginationOptions();
        
        if ($perPage === 'all') {
            // Store in session for persistence
            session(['per_page' => $perPage]);
            return 'all';
        }
        
        if (in_array((int)$perPage, $validOptions)) {
            // Store in session for persistence
            session(['per_page' => $perPage]);
            return (int)$perPage;
        }
        
        // Return default if invalid
        session(['per_page' => $default]);
        return (int)$default;
    }

    /**
     * Apply pagination to a query builder
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param int|string|null $perPage
     * @param string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    protected function applyPagination($query, $perPage = null, string $pageName = 'page')
    {
        if ($perPage === null) {
            $perPage = $this->getPerPagePreference();
        }
        
        // Handle 'all' option
        if ($perPage === 'all') {
            return $query->get();
        }
        
        return $query->paginate($perPage, ['*'], $pageName);
    }
}