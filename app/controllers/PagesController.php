<?php

class PagesController extends Controller
{
  public function index()
  {
    // عدّلها للوحة التحكم عندك
    header('Location: ' . (defined('URLROOT') ? URLROOT : '/it_inventory/public') . '/?page=dashboard/index');
    exit;
  }
}
