<?php

use Latte\Runtime as LR;

/** source: home.latte */
final class Template_a754daae06 extends Latte\Runtime\Template
{
	public const Source = 'home.latte';

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

		$this->parentName = '_mainLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		echo '    <!-- Main Content -->
    <div class="container py-4" style="flex:1">        
        <h1>Willkommen im Dashboard</h1>
        <p>Hier steht dein Content...</p>  
    </div>
';
	}
}
