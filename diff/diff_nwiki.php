<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A PHP diff engine for phpwiki. (Taken from phpwiki-1.3.3)
 * Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * You may copy this code freely under the conditions of the GPL.
 */
define('USE_ASSERTS_IN_WIKI', function_exists('assert'));

class wikidiffop {

    public $type;
    public $orig;
    public $closing;

    public function reverse() {
        trigger_error("pure virtual", E_USER_ERROR);
    }

    public function norig() {
        return $this->orig ? count($this->orig) : 0;
    }

    public function nclosing() {
        return $this->closing ? count($this->closing) : 0;
    }

}

class wikidiffop_copy extends wikidiffop {

    public $type = 'copy';

    public function __construct($orig, $closing = false) {
        if (!is_array($closing)) {
            $closing = $orig;
        }
        $this->orig = $orig;
        $this->closing = $closing;
    }

    public function reverse() {
        return new wikidiffop_copy($this->closing, $this->orig);
    }

}

class wikidiffop_delete extends wikidiffop {

    public $type = 'delete';

    public function __construct($lines) {
        $this->orig = $lines;
        $this->closing = false;
    }

    public function reverse() {
        return new wikidiffop_add($this->orig);
    }

}

class wikidiffop_add extends wikidiffop {

    public $type = 'add';

    public function __construct($lines) {
        $this->closing = $lines;
        $this->orig = false;
    }

    public function reverse() {
        return new wikidiffop_delete($this->closing);
    }

}

class wikidiffop_change extends wikidiffop {

    public $type = 'change';

    public function __construct($orig, $closing) {
        $this->orig = $orig;
        $this->closing = $closing;
    }

    public function reverse() {
        return new wikidiffop_change($this->closing, $this->orig);
    }

}

/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 * http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from:
 * http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas are (and a bit of code) are from from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 * ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * closingly, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 *
 * @author Geoffrey T. Dairiki
 * @access private
 */
class wikidiffengine {

    public function diff($fromlines, $tolines) {
        $nfrom = count($fromlines);
        $nto = count($tolines);

        $this->xchanged = $this->ychanged = array();
        $this->xv = $this->yv = array();
        $this->xind = $this->yind = array();
        unset($this->seq);
        unset($this->in_seq);
        unset($this->lcs);

        // Skip leading common lines.
        for ($skip = 0; $skip < $nfrom && $skip < $nto; $skip++) {
            if ($fromlines[$skip] != $tolines[$skip]) {
                break;
            }
            $this->xchanged[$skip] = $this->ychanged[$skip] = false;
        }
        // Skip trailing common lines.
        $xi = $nfrom;
        $yi = $nto;
        for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
            if ($fromlines[$xi] != $tolines[$yi]) {
                break;
            }
            $this->xchanged[$xi] = $this->ychanged[$yi] = false;
        }

        // Ignore lines which do not exist in both files.
        for ($xi = $skip; $xi < $nfrom - $endskip; $xi++) {
            $xhash[$fromlines[$xi]] = 1;
        }
        for ($yi = $skip; $yi < $nto - $endskip; $yi++) {
            $line = $tolines[$yi];
            if (($this->ychanged[$yi] = empty($xhash[$line]))) {
                continue;
            }
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $nfrom - $endskip; $xi++) {
            $line = $fromlines[$xi];
            if (($this->xchanged[$xi] = empty($yhash[$line]))) {
                continue;
            }
            $this->xv[] = $line;
            $this->xind[] = $xi;
        }

        // Find the LCS.
        $this->_compareseq(0, count($this->xv), 0, count($this->yv));

        // Merge edits when possible.
        $this->_shift_boundaries($fromlines, $this->xchanged, $this->ychanged);
        $this->_shift_boundaries($tolines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $edits = array();
        $xi = $yi = 0;
        while ($xi < $nfrom || $yi < $nto) {
            USE_ASSERTS_IN_WIKI && assert($yi < $nto || $this->xchanged[$xi]);
            USE_ASSERTS_IN_WIKI && assert($xi < $nfrom || $this->ychanged[$yi]);

            // Skip matching "snake".
            $copy = array();
            while ($xi < $nfrom && $yi < $nto && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $fromlines[$xi++];
                ++$yi;
            }
            if ($copy) {
                $edits[] = new wikidiffop_copy($copy);
            }

            // Find deletes & adds.
            $delete = array();
            while ($xi < $nfrom && $this->xchanged[$xi]) {
                $delete[] = $fromlines[$xi++];
            }

            $add = array();
            while ($yi < $nto && $this->ychanged[$yi]) {
                $add[] = $tolines[$yi++];
            }

            if ($delete && $add) {
                $edits[] = new wikidiffop_change($delete, $add);
            } else if ($delete) {
                $edits[] = new wikidiffop_delete($delete);
            } else if ($add) {
                $edits[] = new wikidiffop_add($add);
            }
        }
        return $edits;
    }

    /* Divide the Largest Common Subsequence (LCS) of the sequences
     * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
     * sized segments.
     *
     * Returns (LCS, PTS). LCS is the length of the LCS. PTS is an
     * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
     * sub sequences.  The first sub-sequence is contained in [X0, X1),
     * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on.  Note
     * that (X0, Y0) == (XOFF, YOFF) and
     * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
     *
     * This function assumes that the first lines of the specified portions
     * of the two files do not match, and likewise that the last lines do not
     * match.  The caller must trim matching lines from the beginning and end
     * of the portions it is going to specify.
     */

    public function _diag($xoff, $xlim, $yoff, $ylim, $nchunks) {
        $flip = false;

        if ($xlim - $xoff > $ylim - $yoff) {
            // Things seems faster (I'm not sure I understand why)
            // when the shortest sequence in X.
            $flip = true;
            list ($xoff, $xlim, $yoff, $ylim) = array($yoff, $ylim, $xoff, $xlim);
        }

        if ($flip) {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->xv[$i]][] = $i;
            }
        } else {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->yv[$i]][] = $i;
            }
        }

        $this->lcs = 0;
        $this->seq[0] = $yoff - 1;
        $this->in_seq = array();
        $ymids[0] = array();

        $numer = $xlim - $xoff + $nchunks - 1;
        $x = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0) {
                for ($i = 0; $i <= $this->lcs; $i++) {
                    $ymids[$i][$chunk - 1] = $this->seq[$i];
                }
            }

            $x1 = $xoff + (int) (($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line])) {
                    continue;
                }
                $matches = $ymatches[$line];
                reset($matches);
                while (list ($junk, $y) = each($matches)) {
                    if (empty($this->in_seq[$y])) {
                        $k = $this->_lcs_pos($y);
                        USE_ASSERTS_IN_WIKI && assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }
                while (list ($junk, $y) = each($matches)) {
                    if ($y > $this->seq[$k - 1]) {
                        USE_ASSERTS_IN_WIKI && assert($y < $this->seq[$k]);
                        // Optimization: this is a common case:
                        // next match is just replacing previous match.
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k] = $y;
                        $this->in_seq[$y] = 1;
                    } else if (empty($this->in_seq[$y])) {
                        $k = $this->_lcs_pos($y);
                        USE_ASSERTS_IN_WIKI && assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }

        $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
        $ymid = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1 = $xoff + (int) (($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1 = $ymid[$n] + 1;
            $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
        }
        $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

        return array($this->lcs, $seps);
    }

    public function _lcs_pos($ypos) {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos] = 1;
            return $this->lcs;
        }

        $beg = 1;
        while ($beg < $end) {
            $mid = (int) (($beg + $end) / 2);
            if ($ypos > $this->seq[$mid]) {
                $beg = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        USE_ASSERTS_IN_WIKI && assert($ypos != $this->seq[$end]);

        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end] = $ypos;
        $this->in_seq[$ypos] = 1;
        return $end;
    }

    /* Find LCS of two sequences.
     *
     * The results are recorded in the vectors $this->{x,y}changed[], by
     * storing a 1 in the element for each line that is an insertion
     * or deletion (ie. is not in the LCS).
     *
     * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
     *
     * Note that XLIM, YLIM are exclusive bounds.
     * All line numbers are origin-0 and discarded lines are not counted.
     */

    public function _compareseq($xoff, $xlim, $yoff, $ylim) {
        // Slide down the bottom initial diagonal.
        while ($xoff < $xlim && $yoff < $ylim && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }

        // Slide up the top initial diagonal.
        while ($xlim > $xoff && $ylim > $yoff && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }

        if ($xoff == $xlim || $yoff == $ylim) {
            $lcs = 0;
        } else {
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list ($lcs, $seps) = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }

        if ($lcs == 0) {
            // X and Y sequences have no common subsequence: mark all changed.
            while ($yoff < $ylim) {
                $this->ychanged[$this->yind[$yoff++]] = 1;
            }
            while ($xoff < $xlim) {
                $this->xchanged[$this->xind[$xoff++]] = 1;
            }
        } else {
            // Use the partitions to split this problem into subproblems.
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->_compareseq($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }

    /* Adjust inserts/deletes of identical lines to join changes
     * as much as possible.
     *
     * We do something when a run of changed lines include a
     * line at one end and has an excluded, identical line at the other.
     * We are free to choose which identical line is included.
     * `compareseq' usually chooses the one at the beginning,
     * but usually it is cleaner to consider the following identical line
     * to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */

    public function _shift_boundaries($lines, &$changed, $otherchanged) {
        $i = 0;
        $j = 0;

        USE_ASSERTS_IN_WIKI && assert('count($lines) == count($changed)');
        $len = count($lines);
        $otherlen = count($otherchanged);

        while (1) {
            /*
             * Scan forwards to find beginning of another run of changes.
             * Also keep track of the corresponding point in the other file.
             *
             * Throughout this code, $i and $j are adjusted together so that
             * the first $i elements of $changed and the first $j elements
             * of $otherchanged both contain the same number of zeros
             * (unchanged lines).
             * Furthermore, $j is always kept so that $j == $otherlen or
             * $otherchanged[$j] == false.
             */
            while ($j < $otherlen && $otherchanged[$j]) {
                $j++;
            }

            while ($i < $len && !$changed[$i]) {
                USE_ASSERTS_IN_WIKI && assert('$j < $otherlen && ! $otherchanged[$j]');
                $i++;
                $j++;
                while ($j < $otherlen && $otherchanged[$j]) {
                    $j++;
                }
            }

            if ($i == $len) {
                break;
            }

            $start = $i;

            // Find the end of this run of changes.
            while (++$i < $len && $changed[$i]) {
                continue;
            }

            do {
                /*
                 * Record the length of this run of changes, so that
                 * we can later determine whether the run has grown.
                 */
                $runlength = $i - $start;

                /*
                 * Move the changed region back, so long as the
                 * previous unchanged line matches the last changed one.
                 * This merges with previous changed regions.
                 */
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i] = false;
                    while ($start > 0 && $changed[$start - 1]) {
                        $start--;
                    }
                    USE_ASSERTS_IN_WIKI && assert('$j > 0');
                    while ($otherchanged[--$j]) {
                        continue;
                    }
                    USE_ASSERTS_IN_WIKI && assert('$j >= 0 && !$otherchanged[$j]');
                }

                /*
                 * Set CORRESPONDING to the end of the changed run, at the last
                 * point where it corresponds to a changed run in the other file.
                 * CORRESPONDING == LEN means no such point has been found.
                 */
                $corresponding = $j < $otherlen ? $i : $len;

                /*
                 * Move the changed region forward, so long as the
                 * first changed line matches the following unchanged one.
                 * This merges with following changed regions.
                 * Do this second, so that if there are no merges,
                 * the changed region is moved forward as far as possible.
                 */
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++] = 1;
                    while ($i < $len && $changed[$i]) {
                        $i++;
                    }

                    USE_ASSERTS_IN_WIKI && assert('$j < $otherlen && ! $otherchanged[$j]');
                    $j++;
                    if ($j < $otherlen && $otherchanged[$j]) {
                        $corresponding = $i;
                        while ($j < $otherlen && $otherchanged[$j]) {
                            $j++;
                        }
                    }
                }
            } while ($runlength != $i - $start);

            /*
             * If possible, move the fully-merged run of changes
             * back to a corresponding run in the other file.
             */
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i] = 0;
                USE_ASSERTS_IN_WIKI && assert('$j > 0');
                while ($otherchanged[--$j]) {
                    continue;
                }
                USE_ASSERTS_IN_WIKI && assert('$j >= 0 && !$otherchanged[$j]');
            }
        }
    }

}

/**
 * Class representing a 'diff' between two sequences of strings.
 */
class wikidiff {

    public $edits;

    /**
     * Constructor.
     * Computes diff between sequences of strings.
     *
     * @param $fromlines array An array of strings.
     * (Typically these are lines from a file.)
     * @param $tolines array An array of strings.
     */
    public function __construct($fromlines, $tolines) {
        $eng = new wikidiffengine;
        $this->edits = $eng->diff($fromlines, $tolines);
    }

    /**
     * Compute reversed wikidiff.
     *
     * SYNOPSIS:
     *
     * $diff = new wikidiff($lines1, $lines2);
     * $rev = $diff->reverse();
     * @return stdClass A wikidiff object representing the inverse of the original diff.
     */
    public function reverse() {
        $rev = $this;
        $rev->edits = array();
        foreach ($this->edits as $edit) {
            $rev->edits[] = $edit->reverse();
        }
        return $rev;
    }

    /**
     * Check for empty diff.
     *
     * @return bool True iff two sequences were identical.
     */
    public function isempty() {
        foreach ($this->edits as $edit) {
            if ($edit->type != 'copy') {
                return false;
            }
        }
        return true;
    }

    /**
     * Compute the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     *
     * @return int The length of the LCS.
     */
    public function lcs() {
        $lcs = 0;
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $lcs += count($edit->orig);
            }
        }
        return $lcs;
    }

    /**
     * Get the original set of lines.
     *
     * This reconstructs the $fromlines parameter passed to the
     * constructor.
     *
     * @return array The original sequence of strings.
     */
    public function orig() {
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, count($lines), 0, $edit->orig);
            }
        }
        return $lines;
    }

    /**
     * Get the closing set of lines.
     *
     * This reconstructs the $tolines parameter passed to the
     * constructor.
     *
     * @return array The sequence of strings.
     */
    public function closing() {
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->closing) {
                array_splice($lines, count($lines), 0, $edit->closing);
            }
        }
        return $lines;
    }

    /**
     * Check a wikidiff for validity.
     *
     * This is here only for debugging purposes.
     */
    public function _check($fromlines, $tolines) {
        if (serialize($fromlines) != serialize($this->orig())) {
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        }
        if (serialize($tolines) != serialize($this->closing())) {
            trigger_error("Reconstructed closing doesn't match", E_USER_ERROR);
        }

        $rev = $this->reverse();
        if (serialize($tolines) != serialize($rev->orig())) {
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        }
        if (serialize($fromlines) != serialize($rev->closing())) {
            trigger_error("Reversed closing doesn't match", E_USER_ERROR);
        }

        $prevtype = 'none';
        foreach ($this->edits as $edit) {
            if ($prevtype == $edit->type) {
                trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
            }
            $prevtype = $edit->type;
        }

        $lcs = $this->lcs();
        trigger_error("wikidiff okay: LCS = $lcs", E_USER_NOTICE);
    }

}

/**
 * FIXME: bad name.
 */
class mappedwikidiff extends wikidiff {

    /**
     * Constructor.
     *
     * Computes diff between sequences of strings.
     *
     * This can be used to compute things like
     * case-insensitve diffs, or diffs which ignore
     * changes in white-space.
     *
     * @param $fromlines array An array of strings.
     * (Typically these are lines from a file.)
     *
     * @param $tolines array An array of strings.
     *
     * @param $mappedfromlines array This array should
     * have the same size number of elements as $fromlines.
     * The elements in $mappedfromlines and $mappedtolines 
     * are what is actually compared when computing the diff.
     *
     * @param $mappedtolines array This array should have
     * the same number of elements as $tolines.
     */
    public function __construct($fromlines, $tolines, $mappedfromlines, $mappedtolines) {

        assert(count($fromlines) == count($mappedfromlines));
        assert(count($tolines) == count($mappedtolines));

        $this->wikidiff($mappedfromlines, $mappedtolines);

        $xi = $yi = 0;
        for ($i = 0; $i < count($this->edits); $i++) {
            $orig = &$this->edits[$i]->orig;
            if (is_array($orig)) {
                $orig = array_slice($fromlines, $xi, count($orig));
                $xi += count($orig);
            }

            $closing = &$this->edits[$i]->closing;
            if (is_array($closing)) {
                $closing = array_slice($tolines, $yi, count($closing));
                $yi += count($closing);
            }
        }
    }

}

/**
 * A class to format wikidiffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 */
class wikidiffformatter {

    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     */
    public $leadingcontextlines = 0;

    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     */
    public $trailingcontextlines = 0;

    /**
     * Format a diff.
     *
     * @param $diff object A wikidiff object.
     * @return string The formatted output.
     */
    public function format($diff) {

        $xi = $yi = 1;
        $block = false;
        $context = array();

        $nlead = $this->leadingcontextlines;
        $ntrail = $this->trailingcontextlines;

        $this->_start_diff();

        foreach ($diff->edits as $edit) {
            if ($edit->type == 'copy') {
                if (is_array($block)) {
                    if (count($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new _WikiwikidiffOp_copy($context);
                        }
                        $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (!is_array($block)) {
                    $context = array_slice($context, count($context) - $nlead);
                    $x0 = $xi - count($context);
                    $y0 = $yi - count($context);
                    $block = array();
                    if ($context) {
                        $block[] = new _WikiwikidiffOp_copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += count($edit->orig);
            }
            if ($edit->closing) {
                $yi += count($edit->closing);
            }
        }

        if (is_array($block)) {
            $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
        }

        return $this->_end_diff();
    }

    public function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {
        $this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
        foreach ($edits as $edit) {
            if ($edit->type == 'copy') {
                $this->_context($edit->orig);
            } else if ($edit->type == 'add') {
                $this->_added($edit->closing);
            } else if ($edit->type == 'delete') {
                $this->_deleted($edit->orig);
            } else if ($edit->type == 'change') {
                $this->_changed($edit->orig, $edit->closing);
            } else {
                trigger_error("Unknown edit type", E_USER_ERROR);
            }
        }
        $this->_end_block();
    }

    public function _start_diff() {
        ob_start();
    }

    public function _end_diff() {
        $val = ob_get_contents();
        ob_end_clean();
        return $val;
    }

    public function _block_header($xbeg, $xlen, $ybeg, $ylen) {
        if ($xlen > 1) {
            $xbeg .= "," . ($xbeg + $xlen - 1);
        }
        if ($ylen > 1) {
            $ybeg .= "," . ($ybeg + $ylen - 1);
        }

        return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
    }

    public function _start_block($header) {
        echo $header;
    }

    public function _end_block() {

    }

    public function _lines($lines, $prefix = ' ') {
        foreach ($lines as $line) {
            echo "$prefix $line\n";
        }
    }

    public function _context($lines) {
        $this->_lines($lines);
    }

    public function _added($lines) {
        $this->_lines($lines, ">");
    }

    public function _deleted($lines) {
        $this->_lines($lines, "<");
    }

    public function _changed($orig, $closing) {
        $this->_deleted($orig);
        echo "---\n";
        $this->_added($closing);
    }

}

/**
 * Additions by Axel Boldt follow, partly taken from diff.php, phpwiki-1.3.3
 */
define('NBSP', '&#160;');   // Non-breaking space.

class wikihwldf_wordaccumulator {

    public function __construct() {
        $this->_lines = array();
        $this->_line = '';
        $this->_group = '';
        $this->_tag = '';
    }

    public function _flushgroup($newtag) {
        if ($this->_group !== '') {
            if ($this->_tag == 'mark') {
                $this->_line .= '<span class="diffchange">' . $this->_group . '</span>';
            } else {
                $this->_line .= $this->_group;
            }
        }
        $this->_group = '';
        $this->_tag = $newtag;
    }

    public function _flushline($newtag) {
        $this->_flushgroup($newtag);
        if ($this->_line != '') {
            $this->_lines[] = $this->_line;
        }
        $this->_line = '';
    }

    public function addwords($words, $tag = '') {
        if ($tag != $this->_tag) {
            $this->_flushgroup($tag);
        }

        foreach ($words as $word) {
            // New-line should only come as first char of word.
            if ($word == '') {
                continue;
            }
            if ($word[0] == "\n") {
                $this->_group .= NBSP;
                $this->_flushline($tag);
                $word = substr($word, 1);
            }
            assert(!strstr($word, "\n"));
            $this->_group .= $word;
        }
    }

    public function getlines() {
        $this->_flushline('~done');
        return $this->_lines;
    }

}

class wordlevelwikidiff extends mappedwikidiff {

    public function __construct($origlines, $closinglines) {
        list ($origwords, $origstripped) = $this->_split($origlines);
        list ($closingwords, $closingstripped) = $this->_split($closinglines);

        $this->mappedwikidiff($origwords, $closingwords, $origstripped, $closingstripped);
    }

    public function _split($lines) {
        if (!preg_match_all('/ ( [^\S\n]+ | [0-9_A-Za-z\x80-\xff]+ | . ) (?: (?!< \n) [^\S\n])? /xs', implode("\n", $lines), $m)) {
            return array(array(''), array(''));
        }
        return array($m[0], $m[1]);
    }

    public function orig() {
        $orig = new wikihwldf_wordaccumulator;

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $orig->addwords($edit->orig);
            } else if ($edit->orig) {
                $orig->addwords($edit->orig, 'mark');
            }
        }
        return $orig->getlines();
    }

    public function closing() {
        $closing = new wikihwldf_wordaccumulator;

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $closing->addwords($edit->closing);
            } else if ($edit->closing) {
                $closing->addwords($edit->closing, 'mark');
            }
        }
        return $closing->getlines();
    }

}

/**
 * @TODO: Doc this class
 */
class tablewikidiffformatter extends wikidiffformatter {

    public $htmltable = array();

    public function __construct() {
        $this->leading_context_lines = 2;
        $this->trailing_context_lines = 2;
    }

    public function _block_header($xbeg, $xlen, $ybeg, $ylen) {

    }

    public function _start_block($header) {

    }

    public function _end_block() {

    }

    public function _lines($lines, $prefix = ' ', $color = "white") {

    }

    public function _added($lines) {
        global $htmltable;
        foreach ($lines as $line) {
            $htmltable[] = array('', '+', '<div class="wiki_diffadd">' . $line . '</div>');
        }
    }

    public function _deleted($lines) {
        global $htmltable;
        foreach ($lines as $line) {
            $htmltable[] = array('<div class="wiki_diffdel">' . $line . '</div>', '-', '');
        }
    }

    public function _context($lines) {
        global $htmltable;
        foreach ($lines as $line) {
            $htmltable[] = array($line, '', $line);
        }
    }

    public function _changed($orig, $closing) {
        global $htmltable;
        $diff = new wordlevelwikidiff($orig, $closing);
        $del = $diff->orig();
        $add = $diff->closing();

        while ($line = array_shift($del)) {
            $aline = array_shift($add);
            $htmltable[] = array('<div class="wiki_diffdel">'
                . $line . '</div>', '-', '<div class="wiki_diffadd">' . $aline . '</div>');
        }
        $this->_added($add); // If any leftovers.
    }

    public function get_result() {
        global $htmltable;
        return $htmltable;
    }

}

/**
 * Wikipedia Table style diff formatter.
 */
class tablewikidiffformatterold extends wikidiffformatter {

    public function tablewikidiffformatter() {
        $this->leading_context_lines = 2;
        $this->trailing_context_lines = 2;
    }

    public function _block_header($xbeg, $xlen, $ybeg, $ylen) {
        $l1 = wfMsg("lineno", $xbeg);
        $l2 = wfMsg("lineno", $ybeg);

        $r = '<tr><td colspan="2" align="left"><strong>' . $l1 . "</strong></td>\n" .
                '<td colspan="2" align="left"><strong>' . $l2 . "</strong></td></tr>\n";
        return $r;
    }

    public function _start_block($header) {
        global $wgout;
        $wgout->addHTML($header);
    }

    public function _end_block() {

    }

    public function _lines($lines, $prefix = ' ', $color = "white") {

    }

    public function addedline($line) {
        return '<td>+</td><td class="diff-addedline">' .
                $line . '</td>';
    }

    public function deletedline($line) {
        return '<td>-</td><td class="diff-deletedline">' .
                $line . '</td>';
    }

    public function emptyline() {
        return '<td colspan="2">&nbsp;</td>';
    }

    public function contextline($line) {
        return '<td> </td><td class="diff-context">' . $line . '</td>';
    }

    public function _added($lines) {
        global $wgout;
        foreach ($lines as $line) {
            $wgout->addHTML('<tr>' . $this->emptyline() .
                    $this->addedline($line) . "</tr>\n");
        }
    }

    public function _deleted($lines) {
        global $wgout;
        foreach ($lines as $line) {
            $wgout->addHTML('<tr>' . $this->deletedline($line) .
                    $this->emptyline() . "</tr>\n");
        }
    }

    public function _context($lines) {
        global $wgout;
        foreach ($lines as $line) {
            $wgout->addHTML('<tr>' . $this->contextline($line) .
                    $this->contextline($line) . "</tr>\n");
        }
    }

    public function _changed($orig, $closing) {
        global $wgout;
        $diff = new wordlevelwikidiff($orig, $closing);
        $del = $diff->orig();
        $add = $diff->closing();

        while ($line = array_shift($del)) {
            $aline = array_shift($add);
            $wgout->addHTML('<tr>' . $this->deletedline($line) .
                    $this->addedline($aline) . "</tr>\n");
        }
        $this->_added($add); // If any leftovers.
    }

}
