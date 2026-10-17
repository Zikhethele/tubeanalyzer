<?php
declare(strict_types=1);

/** URL slug for a channel name: lowercase a–z/0–9 runs joined by single hyphens. */
function channelSlug(string $name): string {
    return trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');
}

/**
 * The slug a channel page should live at: taken from the YouTube display name when
 * that yields one, otherwise from what the visitor typed. Keeps one URL per channel
 * however it was entered ("andilekamajola" vs "Andile Kamajola").
 */
function canonicalChannelSlug(?string $displayName, string $typedName): string {
    $slug = channelSlug((string) $displayName);
    return $slug !== '' ? $slug : channelSlug($typedName);
}

/** Whether stored analysis data is complete enough for its page to be indexed. */
function channelIsIndexable(?array $data): bool {
    return is_array($data)
        && !empty($data['channel_id'])
        && isset($data['subscribers'])
        && !empty($data['videos'])
        && is_array($data['videos']);
}
