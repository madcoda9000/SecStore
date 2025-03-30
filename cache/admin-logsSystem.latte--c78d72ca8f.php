<?php

use Latte\Runtime as LR;

/** source: admin/logsSystem.latte */
final class Template_c78d72ca8f extends Latte\Runtime\Template
{
	public const Source = 'admin/logsSystem.latte';

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
    <h1 class="mb-5">View - Systemlogs</h1>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <label for="pageSize" class="me-2 desktop-pager-label">Einträge pro Seite:</label>
            <select id="pageSize" class="form-select d-inline w-auto">
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div>
            <div class="input-group">
                <input type="text" id="search" class="form-control" placeholder="Search...">
            </div>
        </div>
    </div>
    
    <!-- lade indicator -->
    <div id="loadingSpinner" style="display: none; text-align: center;">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>     

    <table class="table table-striped desktop-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Date</th>
                <th>user</th>
                <th>Context</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody id="logTableBody">
        </tbody>
    </table>

    <!-- Für Mobile: Kartenansicht -->
    <div id="logCardsBody" class="d-block d-md-none desktop-table"></div>
    
    <div class="d-flex justify-content-between">
        <span id="paginationInfo" class="desktop-pager-summary">Seite 1 von X</span>
        <nav>
            <ul class="pagination" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- include js -->
<script src="/js/admin/logsSystem.latte-min.js"></script>

<!-- fetch logs initial -->
<script>
    fetchLogs();
</script>
';
	}
}
