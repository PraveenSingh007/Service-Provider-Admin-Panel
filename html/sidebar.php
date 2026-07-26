<?php

declare(strict_types=1);

/**
 * Shared Sidebar Menu Component
 * Standardizes complete navigation menu across all application pages.
 */
$activePage = $activePage ?? '';
?>
<!-- Sidebar Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="dashboard.php" class="app-brand-link">
      <span class="app-brand-text demo menu-text fw-bold ms-2">Sneat</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
    </a>
  </div>

  <div class="menu-divider mt-0"></div>
  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Dashboard -->
    <li class="menu-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <a href="dashboard.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-smile"></i>
        <div class="text-truncate">Dashboard</div>
      </a>
    </li>

    <!-- Employees Section -->
    <?php $isEmployeesOpen = in_array($activePage, ['employees', 'add-employee', 'attendance', 'salaries'], true); ?>
    <li class="menu-item <?= $isEmployeesOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-user"></i>
        <div class="text-truncate">Employees</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'employees' ? 'active' : '' ?>">
          <a href="employees.php" class="menu-link">
            <div class="text-truncate">All Employees</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-employee' ? 'active' : '' ?>">
          <a href="add-employee.php" class="menu-link">
            <div class="text-truncate">Add Employee</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'attendance' ? 'active' : '' ?>">
          <a href="employee-attendance.php" class="menu-link">
            <div class="text-truncate">Attendance</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'salaries' ? 'active' : '' ?>">
          <a href="employee-salaries.php" class="menu-link">
            <div class="text-truncate">Salaries & Payslips</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Quotations Section -->
    <?php $isQuotationsOpen = in_array($activePage, ['quotations', 'add-quotation', 'quotation-details'], true); ?>
    <li class="menu-item <?= $isQuotationsOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-receipt"></i>
        <div class="text-truncate">Quotations</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'quotations' ? 'active' : '' ?>">
          <a href="quotations.php" class="menu-link">
            <div class="text-truncate">All Quotations</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-quotation' ? 'active' : '' ?>">
          <a href="add-quotation.php" class="menu-link">
            <div class="text-truncate">Create Quotation</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Invoices Section -->
    <?php $isInvoicesOpen = in_array($activePage, ['invoices', 'generate-invoice', 'invoice-details'], true); ?>
    <li class="menu-item <?= $isInvoicesOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-file"></i>
        <div class="text-truncate">Invoices</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'invoices' ? 'active' : '' ?>">
          <a href="invoices.php" class="menu-link">
            <div class="text-truncate">All Invoices</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'generate-invoice' ? 'active' : '' ?>">
          <a href="generate-invoice.php" class="menu-link">
            <div class="text-truncate">Generate Invoice</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Daily Expenses Section -->
    <?php $isExpensesOpen = in_array($activePage, ['daily-expenses', 'add-daily-expense'], true); ?>
    <li class="menu-item <?= $isExpensesOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-wallet"></i>
        <div class="text-truncate">Daily Expenses</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'daily-expenses' ? 'active' : '' ?>">
          <a href="daily-expenses.php" class="menu-link">
            <div class="text-truncate">All Expenses</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-daily-expense' ? 'active' : '' ?>">
          <a href="add-daily-expense.php" class="menu-link">
            <div class="text-truncate">Add Expense</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Admins Section -->
    <?php $isAdminOpen = in_array($activePage, ['admins', 'add-admin'], true); ?>
    <li class="menu-item <?= $isAdminOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-user-check"></i>
        <div class="text-truncate">Admins</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'admins' ? 'active' : '' ?>">
          <a href="admins.php" class="menu-link">
            <div class="text-truncate">All Admins</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-admin' ? 'active' : '' ?>">
          <a href="add-admin.php" class="menu-link">
            <div class="text-truncate">Add Admin</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Services Section -->
    <?php $isServicesOpen = in_array($activePage, ['services', 'add-service'], true); ?>
    <li class="menu-item <?= $isServicesOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-cog"></i>
        <div class="text-truncate">Services</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'services' ? 'active' : '' ?>">
          <a href="services.php" class="menu-link">
            <div class="text-truncate">All Services</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-service' ? 'active' : '' ?>">
          <a href="add-service.php" class="menu-link">
            <div class="text-truncate">Add Service</div>
          </a>
        </li>
      </ul>
    </li>

    <!-- Service Areas Section -->
    <?php $isServiceAreasOpen = in_array($activePage, ['service-areas', 'add-service-area'], true); ?>
    <li class="menu-item <?= $isServiceAreasOpen ? 'active open' : '' ?>">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-map-pin"></i>
        <div class="text-truncate">Service Area</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item <?= $activePage === 'service-areas' ? 'active' : '' ?>">
          <a href="service-areas.php" class="menu-link">
            <div class="text-truncate">All Service Areas</div>
          </a>
        </li>
        <li class="menu-item <?= $activePage === 'add-service-area' ? 'active' : '' ?>">
          <a href="add-service-area.php" class="menu-link">
            <div class="text-truncate">Add Service Area</div>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</aside>
<!-- / Sidebar Menu -->
