<?php

use Latte\Runtime as LR;

/** source: _topbar.latte */
final class Template_9dfcafb3ec extends Latte\Runtime\Template
{
	public const Source = '_topbar.latte';


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<!-- Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
      <div class="container">
        <!-- Navbar Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Offcanvas for Mobile Menu -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="navbarOffcanvas" aria-labelledby="navbarOffcanvasLabel">
          <div class="offcanvas-header">
            <h5 id="navbarOffcanvasLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
          <div class="offcanvas-body">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link" href="/home">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="/admin/settings">Settings</a>
              </li>
              <!-- Dropdowns -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administration</a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="/admin/users">Users</a></li>
                  <li><a class="dropdown-item" href="/admin/showRoles">Roles</a></li>
                </ul>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" data-bs-toggle="dropdown" aria-expanded="false">Logs</a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown2">
                  <li><a class="dropdown-item" href="/admin/logsAudit">Audit Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/logsRequest">Request Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/logsSystem">System Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/logsDb">Database Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/logsMail">Mail Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/logsError">Error Logs</a></li>
                </ul>
              </li>
            </ul>
          </div>
        </div>

        <!-- Darkmode Toggle + User Dropdown -->
        <div class="d-flex align-items-center">
          <button id="darkmodeToggle" class="btn me-2">
            <i id="darkmodeIcon" class="bi-sun text-light"></i>
          </button>
          <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
		echo LR\Filters::escapeHtmlText($user->username) /* line 52 */;
		echo '</button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="/profile">Profile</a></li>
              <li><a class="dropdown-item" href="/logout">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- alert icons -->
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
      <symbol id="check-circle-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"></path>
      </symbol>
      <symbol id="info-fill" viewBox="0 0 16 16">
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"></path>
      </symbol>
      <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"></path>
      </symbol>
    </svg>

    <script src="/js/_topbar.latte-min.js"></script>';
	}
}
