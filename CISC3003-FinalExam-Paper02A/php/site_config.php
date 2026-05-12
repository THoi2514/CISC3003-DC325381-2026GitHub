<?php
/**
 * Scenario A — site identity (edit before submission / screenshots).
 */
declare(strict_types=1);

const STUDENT_FULL_NAME = 'HOI KAI CHENG';
const STUDENT_ID = 'DC325381';
const FOOTER_LINE = 'CISC3003 Web Programming: ' . STUDENT_FULL_NAME . ' + ' . STUDENT_ID . ' + 2026';

/** 設為 true 時，報名送出失敗會顯示 MySQL 錯誤訊息（僅本機除錯；繳交前改回 false）。 */
const DEBUG_DB = true;
