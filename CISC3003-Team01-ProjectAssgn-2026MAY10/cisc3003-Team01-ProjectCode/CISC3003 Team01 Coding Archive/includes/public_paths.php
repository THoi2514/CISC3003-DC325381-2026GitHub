<?php
declare(strict_types=1);

/**
 * 一次同步 public header / footer / favicon 的相對路徑前綴。
 * 在根目錄頁面：不設變數或 $layoutBase = ''。
 * 在子目錄（admin/、staff/）：在 <head> 內任何 include 之前設 $layoutBase = '../'。
 *
 * 讀取順序：$layoutBase ?? $publicHeaderBase ?? $footerBase ?? ''。
 */
$layoutBase = $layoutBase ?? $publicHeaderBase ?? $footerBase ?? '';
$publicHeaderBase = $layoutBase;
$footerBase = $layoutBase;
$faviconHrefBase = $layoutBase . 'assets/images/brand/';
