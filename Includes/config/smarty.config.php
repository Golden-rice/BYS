<?php
	/** smarty配置
   * 
   */
	return array(
		"()" => array(
			'setTemplateDir'    => ROOT.'view/',
			'setCompileDir'     => ROOT.'~runtime/comps/',
			'setCacheDir'       => ROOT.'~runtime/cache/'
		),

		"=" => array(
			'cache_lifetime'    => 5,
			'caching'           => false,
			'left_delimiter'    => '<{',
			'right_delimiter'   => '}>'
		)
	);