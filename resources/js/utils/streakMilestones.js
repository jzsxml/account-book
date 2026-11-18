const SEEN_KEY = 'account-book-milestone-seen';

export function getSeenMilestoneIds() {
    try {
        const raw = localStorage.getItem(SEEN_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function markMilestonesSeen(ids) {
    const merged = [...new Set([...getSeenMilestoneIds(), ...ids])];
    localStorage.setItem(SEEN_KEY, JSON.stringify(merged));
}

/**
 * @param {Array<{ id: string, label: string, earned: boolean }>} milestones
 * @returns {Array<{ id: string, label: string }>}
 */
export function popNewlyEarnedMilestones(milestones) {
    if (!milestones?.length) {
        return [];
    }
    const seen = getSeenMilestoneIds();
    const fresh = milestones.filter((m) => m.earned && !seen.includes(m.id));
    if (fresh.length) {
        markMilestonesSeen(fresh.map((m) => m.id));
    }
    return fresh;
}