<?php

use Latte\Runtime as LR;

/** source: admin/users.latte */
final class Template_c9ab76c38a extends Latte\Runtime\Template
{
	public const Source = 'admin/users.latte';

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

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['use' => '98, 154'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		$this->parentName = '../_mainLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		echo '
    <!-- Bootstrap Modal -->
    <div class="modal fade flyout" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteLabel">Benutzer löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bist du sicher, dass du diesen Benutzer löschen möchtest?</p>
                    <input type="hidden" id="deleteUserId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Löschen</button>
                </div>
            </div>
        </div>
    </div>

   <!-- Main Content -->
    <div class="container py-4" style="flex:1">
    <h1 class="mb-5">Benutzerverwaltung</h1>

';
		if (isset($success)) /* line 29 */ {
			echo '        <div class="alert alert-success d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill"></use></svg>
            <div>
                ';
			echo LR\Filters::escapeHtmlText($success) /* line 33 */;
			echo '
            </div>
        </div>
';
		}
		echo "\n";
		if (isset($error)) /* line 38 */ {
			echo '        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"></use></svg>
            <div>
                ';
			echo LR\Filters::escapeHtmlText($error) /* line 42 */;
			echo '
            </div>
        </div>
';
		}
		echo "\n";
		if (isset($message)) /* line 47 */ {
			echo '        <div class="alert alert-primary d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#info-fill"></use></svg>
            <div>
                ';
			echo LR\Filters::escapeHtmlText($message) /* line 51 */;
			echo '
            </div>
        </div>
';
		}
		echo '
    <div class="d-flex justify-content-between align-items-left mb-3">
        <!-- Dropdown links -->
        <form method="get" class="usrPageSize">
            <select name="pageSize"  class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="10"';
		$ʟ_tmp = ['selected' => $pageSize == 10];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 60 */;
		echo '>10</option>
                <option value="25"';
		$ʟ_tmp = ['selected' => $pageSize == 25];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 61 */;
		echo '>25</option>
                <option value="50"';
		$ʟ_tmp = ['selected' => $pageSize == 50];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 62 */;
		echo '>50</option>
            </select>
        </form>

        <!-- Suchfeld + Button rechts -->
        <form id="tblHeadBar" method="get" class="d-flex align-items-center gap-2">
            <div class="input-group"> 
            <input type="text" name="search" id="searchInput" value="';
		echo LR\Filters::escapeHtmlAttr($search) /* line 69 */;
		echo '" placeholder="Suche Benutzer" class="form-control form-control-sm w-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi-search"></i></button>
            <button type="button" class="btn btn-success btn-sm" onclick="window.location.href=\'/admin/showCreateUser\'"><i class="bi-person-add"></i></button>
            </div>
        </form>
    </div>

    <!-- lade indicator -->
    <div id="loadingIndicator" style="display: none; text-align: center;">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Desktop-Version der Tabelle -->
    <div class="table-responsive desktop-table">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Ac</th>
                    <th>2FA</th>
                    <th>Enf</th>
                    <th>Benutzername</th>
                    <th>Email</th>                    
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
';
		foreach ($users as $use) /* line 98 */ {
			echo '                    <tr>
                        <td>';
			echo LR\Filters::escapeHtmlText($use->id) /* line 100 */;
			echo '</td>
                        <td style="min-width:40px !important;text-align:center;">
';
			if ($use->username === 'super.admin') /* line 102 */ {
				echo '                                <i id="accountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 103 */;
				echo '" class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="Super-Admin-Account kann nicht geändert werden."></i>
';
			} elseif ($use->status === 1) /* line 104 */ {
				echo '                                <i id="accountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 105 */;
				echo '" class="bi-check-circle-fill text-success" onclick="toggleUserAccountStatus(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 105 */;
				echo ', \'disable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Disable Useraccount."></i>
';
			} else /* line 106 */ {
				echo '                                <i id="accountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 107 */;
				echo '" class="bi-x-circle-fill text-danger" onclick="toggleUserAccountStatus(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 107 */;
				echo ', \'enable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Enable Useraccount."></i>
';
			}

			echo '                        </td>
                        <td style="min-width:40px !important;text-align:center;">
';
			if ($use->mfaSecret !== '' && $use->mfaEnabled === 1) /* line 111 */ {
				echo '                                <i id="mfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 112 */;
				echo '" class="bi-check-circle-fill text-success" data-bs-toggle="tooltip" title="Disable 2FA." onclick="toggleMfa(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 112 */;
				echo ', \'disable\');" style="cursor:pointer;"></i>
';
			} elseif ($use->mfaSecret !== '' && $use->mfaEnabled === 0) /* line 113 */ {
				echo '                                <i id="mfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 114 */;
				echo '" class="bi-x-circle-fill text-danger" data-bs-toggle="tooltip" title="Enable 2FA." onclick="toggleMfa(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 114 */;
				echo ', \'enable\');" style="cursor:pointer;"></i>
';
			} elseif ($use->mfaSecret === '') /* line 115 */ {
				echo '                                <i id="mfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 116 */;
				echo '" class="bi-x-circle-fill text-secondary" data-bs-toggle="tooltip" title="User has not setup 2fa!"></i>
';
			}


			echo '                        </td>
                        <td style="min-width:40px !important;text-align:center;">
';
			if ($use->username === 'super.admin') /* line 120 */ {
				echo '                                <i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA enforcement cannot be changed for super.admin."></i>
';
			} elseif ($use->mfaSecret !== '') /* line 122 */ {
				echo '                                <i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA is configured already."></i>
';
			} elseif ($use->mfaEnforced === 1) /* line 124 */ {
				echo '                                <i id="enforceStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 125 */;
				echo '" class="bi-check-circle-fill text-warning" onclick="toggleMfaEnforcement(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 125 */;
				echo ', \'disable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to unenforce 2FA for user."></i>
';
			} else /* line 126 */ {
				echo '                                <i id="enforceStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 127 */;
				echo '" class="bi-x-circle-fill text-danger" onclick="toggleMfaEnforcement(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 127 */;
				echo ', \'enable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to enforce 2FA for user."></i>
';
			}


			echo '                        </td>
                        <td class="text-nowrap">';
			echo LR\Filters::escapeHtmlText($use->username) /* line 130 */;
			echo '</td>
                        <td class="w-100">';
			echo LR\Filters::escapeHtmlText($use->email) /* line 131 */;
			echo '</td>                        
                        <td class="text-nowrap" style="white-space: nowrap; width: 1%;">
';
			if ($use->username === 'super.admin') /* line 133 */ {
				echo '                                <button class="btn btn-sm btn-danger" disabled>
                                    <i class="bi-trash-fill"></i>
                                </button>
';
			} else /* line 137 */ {
				echo '                                <a href="#" class="btn btn-sm btn-danger delete-user-btn" data-user-id="';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 138 */;
				echo '">
                                    <i class="bi-trash-fill"></i>
                                </a>
';
			}
			echo '                            <a href="/admin/showEditUser/';
			echo LR\Filters::escapeHtmlAttr($use->id) /* line 142 */;
			echo '" class="btn btn-sm btn-warning">
                                <i class="bi-pencil-fill"></i>
                            </a>
                        </td>
                    </tr>
';

		}

		echo '            </tbody>
        </table>
    </div>

    <!-- Mobile-Version als Cards -->
    <div class="mobile-cards d-md-none" id="mobile-cards">
';
		foreach ($users as $use) /* line 154 */ {
			echo '            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">';
			echo LR\Filters::escapeHtmlText($use->username) /* line 157 */;
			echo '</h5>
                    <p class="card-text">
                        <strong>ID: </strong>';
			echo LR\Filters::escapeHtmlText($use->id) /* line 159 */;
			echo '<br>
                        <strong>Email: </strong>';
			echo LR\Filters::escapeHtmlText($use->email) /* line 160 */;
			echo '<br>
                        <strong>Status: </strong>
';
			if ($use->username === 'super.admin') /* line 162 */ {
				echo '                                <i id="maccountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 163 */;
				echo '" class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="Super-Admin-Account kann nicht geändert werden."></i>
';
			} elseif ($use->status === 1) /* line 164 */ {
				echo '                                <i id="maccountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 165 */;
				echo '" class="bi-check-circle-fill text-success" onclick="toggleUserAccountStatus(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 165 */;
				echo ', \'disable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Disable Useraccount."></i>
';
			} else /* line 166 */ {
				echo '                                <i id="maccountStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 167 */;
				echo '" class="bi-x-circle-fill text-danger" onclick="toggleUserAccountStatus(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 167 */;
				echo ', \'enable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Enable Useraccount."></i>
';
			}

			echo '                            <br>
                        <strong>2FA: </strong>
';
			if ($use->mfaSecret !== '' && $use->mfaEnabled === 1) /* line 170 */ {
				echo '                                <i id="mmfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 171 */;
				echo '" class="bi-check-circle-fill text-success" data-bs-toggle="tooltip" title="Disable 2FA." onclick="toggleMfa(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 171 */;
				echo ', \'disable\');" style="cursor:pointer;"></i>
';
			} elseif ($use->mfaSecret !== '' && $use->mfaEnabled === 0) /* line 172 */ {
				echo '                                <i id="mmfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 173 */;
				echo '" class="bi-x-circle-fill text-danger" data-bs-toggle="tooltip" title="Enable 2FA." onclick="toggleMfa(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 173 */;
				echo ', \'enable\');" style="cursor:pointer;"></i>
';
			} elseif ($use->mfaSecret === '') /* line 174 */ {
				echo '                                <i id="mmfaEnabled';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 175 */;
				echo '" class="bi-x-circle-fill text-secondary" data-bs-toggle="tooltip" title="User has not setup 2fa!"></i>
';
			}


			echo '                            <br>
                        <strong>2FA-Enf: </strong>
';
			if ($use->username === 'super.admin') /* line 178 */ {
				echo '                                <i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA enforcement cannot be changed for super.admin."></i>
';
			} elseif ($use->mfaSecret !== '') /* line 180 */ {
				echo '                                <i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA is configured already."></i>
';
			} elseif ($use->mfaEnforced === 1) /* line 182 */ {
				echo '                                <i id="menforceStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 183 */;
				echo '" class="bi-check-circle-fill text-warning" onclick="toggleMfaEnforcement(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 183 */;
				echo ', \'disable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to unenforce 2FA for user."></i>
';
			} else /* line 184 */ {
				echo '                                <i id="menforceStatus';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 185 */;
				echo '" class="bi-x-circle-fill text-danger" onclick="toggleMfaEnforcement(';
				echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($use->id)) /* line 185 */;
				echo ', \'enable\');" style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to enforce 2FA for user."></i>
';
			}


			echo '                            <br>
                    </p>
';
			if ($use->username === 'super.admin') /* line 188 */ {
				echo '                        <button class="btn btn-sm btn-secondary" disabled>
                            <i class="bi-trash-fill"></i> Löschen
                        </button>
';
			} else /* line 192 */ {
				echo '                        <a href="#" class="btn btn-sm btn-danger delete-user-btn" data-user-id="';
				echo LR\Filters::escapeHtmlAttr($use->id) /* line 193 */;
				echo '">
                            <i class="bi-trash-fill"></i> Löschen
                        </a>
';
			}
			echo '                    <a href="/admin/showEditUser/';
			echo LR\Filters::escapeHtmlAttr($use->id) /* line 197 */;
			echo '" class="btn btn-sm btn-warning">
                        <i class="bi-pencil-fill"></i> Bearbeiten
                    </a>
                </div>
            </div>
';

		}

		echo '    </div>


    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>Seite ';
		echo LR\Filters::escapeHtmlText($page) /* line 207 */;
		echo ' von ';
		echo LR\Filters::escapeHtmlText(ceil($totalUsers / $pageSize)) /* line 207 */;
		echo '</div>
        <div>
';
		if ($page > 1) /* line 209 */ {
			echo '                <a href="?page=';
			echo LR\Filters::escapeHtmlAttr($page - 1) /* line 210 */;
			echo '&pageSize=';
			echo LR\Filters::escapeHtmlAttr($pageSize) /* line 210 */;
			echo '&search=';
			echo LR\Filters::escapeHtmlAttr($search) /* line 210 */;
			echo '" class="btn btn-outline-secondary btn-sm">
                    Vorherige
                </a>
';
		}
		if ($page * $pageSize < $totalUsers) /* line 214 */ {
			echo '                <a href="?page=';
			echo LR\Filters::escapeHtmlAttr($page + 1) /* line 215 */;
			echo '&pageSize=';
			echo LR\Filters::escapeHtmlAttr($pageSize) /* line 215 */;
			echo '&search=';
			echo LR\Filters::escapeHtmlAttr($search) /* line 215 */;
			echo '" class="btn btn-outline-secondary btn-sm">
                    Nächste
                </a>
';
		}
		echo '        </div>
    </div>
</div>


<script src="/js/admin/users.latte.js"></script>
';
	}
}
