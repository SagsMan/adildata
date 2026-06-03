<?php

/**
 * Token verification helper — FAST direct-lookup version.
 *
 * Tokens are stored as plain random hex strings (bin2hex(random_bytes(32))),
 * so we can do a simple indexed WHERE token = ? instead of fetching every
 * row and running password_verify() (bcrypt) on each one — which caused
 * 30-60 s response times.
 *
 * Legacy bcrypt-hashed tokens (from the old api-server/api.php login code)
 * are still supported via a fallback scan so existing sessions keep working
 * until users log in again and get a plain token.
 */
function verifyUserToken($conn, $incomingToken) {

    if (empty($incomingToken)) {
        return ["success" => false, "message" => "Token required"];
    }

    // ── Fast path: plain token indexed lookup ──────────────────────────────
    $ts = mysqli_real_escape_string($conn, $incomingToken);
    $q  = mysqli_query($conn,
        "SELECT id, sname, oname, email, phone, pin, token FROM users_tbl
         WHERE token = '$ts' AND status = 1 LIMIT 1");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return [
            "success" => true,
            "user" => [
                "id"    => $row['id'],
                "name"  => $row['sname'] . " " . $row['oname'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "pin"   => $row['pin'],
            ]
        ];
    }

    // ── Slow legacy fallback: bcrypt-hashed token (old logins only) ───────
    // Once users re-login their token becomes plain text and this path is
    // never hit again for them.
    $q2 = mysqli_query($conn,
        "SELECT id, sname, oname, email, phone, pin, token FROM users_tbl
         WHERE token IS NOT NULL AND token != '' AND status = 1");

    if ($q2) {
        while ($row = mysqli_fetch_assoc($q2)) {
            if (password_verify($incomingToken, $row['token'])) {
                return [
                    "success" => true,
                    "user" => [
                        "id"    => $row['id'],
                        "name"  => $row['sname'] . " " . $row['oname'],
                        "email" => $row['email'],
                        "phone" => $row['phone'],
                        "pin"   => $row['pin'],
                    ]
                ];
            }
        }
    }

    return ["success" => false, "message" => "Invalid or expired token"];
}
