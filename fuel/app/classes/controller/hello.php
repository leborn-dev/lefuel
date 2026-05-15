<?php

class Controller_Hello extends Controller
{
	public function action_index()
	{
		return Response::forge(View::forge('hello/index'));
	}
}
