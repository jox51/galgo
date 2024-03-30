import {
    ArrowTrendingUpIcon,
    ChartBarIcon,
    ShieldCheckIcon,
    StarIcon,
} from "@heroicons/react/24/outline";

const features = [
    {
        name: "Daily Top Picks",
        description:
            "Access a curated list of the day's games with the highest probability of winning, based on extensive data analysis and algorithms.",
        icon: StarIcon,
    },
    {
        name: "Data-Driven Insights",
        description:
            "Our platform leverages advanced analytics to identify opportunities where the odds are in your favor, ensuring you make informed decisions.",
        icon: ChartBarIcon,
    },
    {
        name: "Real-time Updates",
        description:
            "Stay ahead of the game with real-time updates on odds and probabilities, allowing you to make swift decisions based on the latest data.",
        icon: ArrowTrendingUpIcon,
    },
    {
        name: "Secure and Reliable",
        description:
            "Your trust is paramount. We employ advanced security measures to protect your information and ensure a reliable, uninterrupted service.",
        icon: ShieldCheckIcon,
    },
];

export default function Features() {
    return (
        <div className="py-24 sm:py-32 bg-green-50">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="mx-auto max-w-2xl lg:text-center">
                    <h2 className="text-base font-semibold leading-7 text-green-600">
                        Winning Made Easy
                    </h2>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Your Gateway to Consistent Wins
                    </p>
                    <p className="mt-6 text-lg leading-8 text-gray-600">
                        Discover high-probability picks every day, and join a
                        winning community. Make informed bets with our exclusive
                        insights and secure a steady side income.
                    </p>
                </div>
                <div className="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                    <dl className="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                        {features.map((feature) => (
                            <div key={feature.name} className="relative">
                                <dt>
                                    <div className="absolute flex h-12 w-12 items-center justify-center rounded-md bg-green-600 text-white">
                                        <feature.icon
                                            className="h-6 w-6"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <p className="ml-16 text-lg leading-6 font-medium text-gray-900">
                                        {feature.name}
                                    </p>
                                </dt>
                                <dd className="mt-2 ml-16 text-base text-gray-500">
                                    {feature.description}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </div>
    );
}
