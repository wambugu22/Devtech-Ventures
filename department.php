<?php
/**
 * Generic dynamic department page for newly created departments.
 */
require_once __DIR__ . '/includes/functions.php';

$deptSlug = trim((string) ($_GET['dept'] ?? ''));
$dept = get_department_by_slug($deptSlug);
$active = 'dept-' . $deptSlug;

require __DIR__ . '/includes/department-page.php';
