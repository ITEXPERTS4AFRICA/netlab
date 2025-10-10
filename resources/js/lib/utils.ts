import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Format time with leading zeros for consistent display
 * @param timeString - Time string in HH:MM format
 * @returns Formatted time string like "06h30"
 */
export function formatTime(timeString: string): string {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours, 10);
    const minute = parseInt(minutes, 10);

    return `${hour.toString().padStart(2, '0')}h${minute.toString().padStart(2, '0')}`;
}

/**
 * Format time with detailed format for confirmations
 * @param timeString - Time string in HH:MM format
 * @returns Formatted time string like "06H30M"
 */
export function formatTimeDetailed(timeString: string): string {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours, 10);
    const minute = parseInt(minutes, 10);

    return `${hour.toString().padStart(2, '0')}H${minute.toString().padStart(2, '0')}M`;
}

/**
 * Format time range for display
 * @param startTime - Start time string in HH:MM format
 * @param endTime - End time string in HH:MM format
 * @returns Formatted time range like "06h30 - 10h30"
 */
export function formatTimeRange(startTime: string, endTime: string): string {
    return `${formatTime(startTime)} - ${formatTime(endTime)}`;
}

/**
 * Format duration in hours with decimal precision
 * @param hours - Duration in hours (can be decimal)
 * @returns Formatted duration string like "4.0h"
 */
export function formatDuration(hours: number | null): string {
    if (hours === null || hours === undefined) return '0.0h';
    return `${hours.toFixed(1)}h`;
}

/**
 * Calculate duration between two time strings
 * @param startTime - Start time in HH:MM format
 * @param endTime - End time in HH:MM format
 * @returns Duration in hours (decimal)
 */
export function calculateDuration(startTime: string, endTime: string): number {
    const [startHour, startMinute] = startTime.split(':').map(Number);
    const [endHour, endMinute] = endTime.split(':').map(Number);

    const startInMinutes = startHour * 60 + startMinute;
    const endInMinutes = endHour * 60 + endMinute;

    const durationInMinutes = endInMinutes - startInMinutes;
    return durationInMinutes / 60; // Convert to hours
}

/**
 * Format duration from time strings
 * @param startTime - Start time in HH:MM format
 * @param endTime - End time in HH:MM format
 * @returns Formatted duration string like "4H:00M"
 */
export function formatDurationFromTimes(startTime: string, endTime: string): string {
    const duration = calculateDuration(startTime, endTime);
    const hours = Math.floor(duration);
    const minutes = Math.round((duration - hours) * 60);

    if (minutes === 0) {
        return `${hours}H:00M`;
    }

    return `${hours}H:${minutes.toString().padStart(2, '0')}M`;
}
