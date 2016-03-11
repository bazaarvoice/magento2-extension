<?php
/**
 * Tick function for execTimer.
 *
 * @param int ($start) - start time in ms
 * @param int ($exec_time_ms) - execution time in ms
 * @param bool ($is_bot) - shows the mode in which script was run
 */
function tick_timer($start, $exec_time, $is_bot) {
  static $once = true;
  if ((microtime(1) - $start) > $exec_time) {
    if ($once) {
      $once = false;
      throw new Exception('Execution timed out' . ($is_bot ? ' for search bot' : '') . ', exceeded ' . $exec_time * 1000 . 'ms');
    }
  }
}