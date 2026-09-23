import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
    compact = false,
}: {
    user: User;
    showEmail?: boolean;
    compact?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-full bg-secondary text-secondary-foreground">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div
                className={
                    compact
                        ? 'hidden w-max max-w-40 text-left text-sm leading-tight sm:grid'
                        : 'grid flex-1 text-left text-sm leading-tight'
                }
            >
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-sm text-muted-foreground">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
