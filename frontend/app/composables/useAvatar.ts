const avatarColors = [
  'bg-violet-600',
  'bg-blue-600',
  'bg-emerald-600',
  'bg-amber-600',
  'bg-rose-600',
  'bg-cyan-600',
]

export function useAvatar() {
  function avatarColor(id: number): string {
    return avatarColors[id % avatarColors.length]
  }

  function initials(name: string): string {
    return name.split(' ').map((w) => w[0]).join('').substring(0, 2).toUpperCase()
  }

  return { avatarColor, initials }
}
