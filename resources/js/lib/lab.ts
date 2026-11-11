export function allows(permissions: unknown, permission: string): boolean {
    return (
        Array.isArray(permissions) &&
        (permissions.includes('*') || permissions.includes(permission))
    );
}

export function rupees(paise: number): string {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(paise / 100);
}
