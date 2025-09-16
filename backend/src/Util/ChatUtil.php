<?php

namespace App\Util;

/**
 * Class ChatUtil
 *
 * Utility class for chat system helpers
 *
 * @package App\Util
 */
class ChatUtil
{
    /**
     * Generate unique chat identifier based on user ids
     *
     * @param int $userAId The user id
     * @param int $userBId The user id
     *
     * @return string The chat identifier hash
     */
    public function generateChatId(int $userAId, int $userBId): string
    {
        $ids = [$userAId, $userBId];

        // sort ids in ascending order
        sort($ids);

        // generate chat identifier hash
        return hash('sha256', $ids[0] . '_' . $ids[1]);
    }
}
