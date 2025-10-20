<?php

declare(strict_types=1);

final class Currency
{
	/**
	 * Format a number as INR with symbol and thousand separators.
	 */
	public static function inr(float $amount): string
	{
		return '₹' . number_format($amount, 2, '.', ',');
	}

	/**
	 * Format raw price values stored as float/decimal to display
	 */
	public static function format(float $amount): string
	{
		return self::inr($amount);
	}
}
