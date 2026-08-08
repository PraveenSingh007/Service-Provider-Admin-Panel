<?php

declare(strict_types=1);

/**
 * Role-Based Access Control (RBAC) Permission Helper
 */
if (!function_exists('hasModulePermission')) {
    /**
     * Check if a specific user role has permission to access a module.
     *
     * @param string $role
     * @param string $module
     * @return bool
     */
    function hasModulePermission(string $role, string $module): bool
    {
        $roleKey = strtolower(str_replace([' ', '-'], '_', trim($role)));
        $module = strtolower(trim($module));

        // CRITICAL RULE: Company Profile Module is strictly restricted ONLY to Super Administrator
        if (in_array($module, ['company_profile', 'company'], true)) {
            return in_array($roleKey, ['super_admin', 'super_administrator'], true);
        }

        // Quotations and Invoices are hidden/disabled per client request
        if (in_array($module, ['quotations', 'invoices'], true)) {
            return false;
        }

        // Full access roles for other modules: Admin, Administrator, Super Admin, Super Administrator, Manager
        $fullAccessRoles = [
            'admin',
            'administrator',
            'super_admin',
            'super_administrator',
            'manager'
        ];

        if (in_array($roleKey, $fullAccessRoles, true)) {
            return true;
        }

        // Role-based permissions matrix
        $permissions = [
            'site_engineer' => [
                'attendance',
                'quotations',
                'invoices',
                'daily_expenses',
                'service_requests',
                'callback_requests',
            ],
            'office_staff' => [
                'services',
                'employees',
                'attendance',
                'quotations',
                'invoices',
                'service_requests',
                'callback_requests',
            ],
            'office_incharge' => [
                'services',
                'employees',
                'attendance',
                'salaries',
                'quotations',
                'invoices',
                'daily_expenses',
                'service_requests',
                'callback_requests',
            ],
        ];

        if (isset($permissions[$roleKey])) {
            return in_array($module, $permissions[$roleKey], true);
        }

        // Default fallback for any unlisted role
        return false;
    }
}

if (!function_exists('enforceModulePermission')) {
    /**
     * Enforce module permission or redirect user to dashboard.
     *
     * @param string $role
     * @param string $module
     */
    function enforceModulePermission(string $role, string $module): void
    {
        if (!hasModulePermission($role, $module)) {
            header('Location: dashboard.php');
            exit;
        }
    }
}
