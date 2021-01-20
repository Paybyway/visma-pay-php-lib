<?php

namespace VismaPay;

interface VismaPayConnector
{
	public function request($url, $post_arr);
}
