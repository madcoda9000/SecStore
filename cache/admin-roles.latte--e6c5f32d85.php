<?php

use Latte\Runtime as LR;

/** source: admin/roles.latte */
final class Template_e6c5f32d85 extends Latte\Runtime\Template
{
	public const Source = 'admin/roles.latte';

	public const Blocks = [
		['content' => 'blockContent'],
	];


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo "\n";
		$this->renderBlock('content', get_defined_vars()) /* line 3 */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = '../_mainLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		echo '
<div class="container py-4" style="flex:1">
<h1 class="mb-5">Rollenverwaltung</h1>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <label for="pageSize" class="me-2">Einträge pro Seite:</label>
            <select id="pageSize" class="form-select d-inline w-auto">
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div>
            <div class="input-group">
                <input type="text" id="search" class="form-control" placeholder="Suche Rolle...">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Neue Rolle erstellen</button>
            </div>
        </div>
    </div>
    
    <!-- lade indicator -->
    <div id="loadingSpinner" style="display: none; text-align: center;">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Rollenname</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody id="rolesTableBody">
        </tbody>
    </table>

    <div class="d-flex justify-content-between">
        <span id="paginationInfo">Seite 1 von X</span>
        <nav>
            <ul class="pagination" id="pagination"></ul>
        </nav>
    </div>

    
</div>

<div class="modal fade flyout" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neue Rolle erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="newRoleName" class="form-control" placeholder="Rollenname">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" onclick="addRole()">Speichern</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade flyout" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rolle löschen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="deleteConfirmBtn">Löschen</button>
            </div>
        </div>
    </div>
</div>

<!-- include js -->
<script src="/js/admin/roles.latte-min.js"></script>

<!-- fetch roles intial -->
<script>
    fetchRoles();
</script>
';
	}
}
