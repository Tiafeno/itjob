<?php

$post_type = get_query_var('post_type');
get_template_part("archive", $post_type);
