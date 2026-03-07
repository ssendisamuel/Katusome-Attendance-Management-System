-- Clean up orphaned lecturer records
-- This removes lecturer records for users who no longer have the lecturer role

DELETE FROM lecturers
WHERE user_id IN (
    SELECT u.id
    FROM users u
    WHERE u.role != 'lecturer'
    AND NOT EXISTS (
        SELECT 1
        FROM user_roles ur
        WHERE ur.user_id = u.id
        AND ur.role = 'lecturer'
        AND ur.is_active = 1
    )
);

-- Check the results
SELECT
    u.id,
    u.name,
    u.email,
    u.role as primary_role,
    l.id as lecturer_id,
    d.name as department
FROM users u
LEFT JOIN lecturers l ON l.user_id = u.id
LEFT JOIN departments d ON d.id = l.department_id
WHERE l.id IS NOT NULL
ORDER BY u.name;
