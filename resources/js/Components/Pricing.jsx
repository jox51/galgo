import { CheckIcon } from "@heroicons/react/20/solid";

const includedFeatures = [
    "Access to daily high-probability picks",
    "Comprehensive game analytics",
    "Exclusive betting strategies",
    "Priority customer support",
];

export default function Pricing() {
    return (
        // Apply a green gradient background
        <div className="py-24 sm:py-32 bg-gradient-to-r from-green-100 to-green-600">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="mx-auto max-w-2xl sm:text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Straightforward Pricing
                    </h2>
                    <p className="mt-6 text-lg leading-8 text-green-100">
                        For one flat monthly fee, unlock access to the day's
                        most likely wins. Simplify your betting process with our
                        expertly curated picks.
                    </p>
                </div>
                <div className="mx-auto mt-16 max-w-2xl rounded-3xl ring-1 ring-gray-200 sm:mt-20 lg:mx-0 lg:flex lg:max-w-none">
                    <div className="bg-white p-8 sm:p-10 lg:flex-auto rounded-lg">
                        <h3 className="text-2xl font-bold tracking-tight text-gray-900">
                            Premium Membership
                        </h3>
                        <p className="mt-6 text-base leading-7 text-gray-600">
                            Gain full access to our platform and transform the
                            way you bet. Make informed decisions with our
                            high-probability picks.
                        </p>
                        <div className="mt-10 flex items-center gap-x-4">
                            <h4 className="flex-none text-sm font-semibold leading-6 text-green-600">
                                Included in all plans
                            </h4>
                            <div className="h-px flex-auto bg-green-200" />
                        </div>
                        <ul
                            role="list"
                            className="mt-8 space-y-4 text-sm leading-6 text-gray-600"
                        >
                            {includedFeatures.map((feature) => (
                                <li key={feature} className="flex gap-x-3">
                                    <CheckIcon
                                        className="h-6 w-5 flex-none text-green-600"
                                        aria-hidden="true"
                                    />
                                    {feature}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="bg-white -mt-2 p-2 lg:mt-0 lg:w-full lg:max-w-md lg:flex-shrink-0 rounded-lg">
                        <div className="rounded-2xl py-10 text-center ring-1 ring-inset ring-green-900/10 lg:flex lg:flex-col lg:justify-center lg:py-16">
                            <div className="mx-auto max-w-xs px-8">
                                <p className="text-base font-semibold text-gray-900">
                                    Join our winning community
                                </p>
                                <p className="mt-6 flex items-baseline justify-center gap-x-2">
                                    <span className="text-5xl font-bold tracking-tight text-gray-900">
                                        $80
                                    </span>
                                    <span className="text-sm font-semibold leading-6 tracking-wide text-gray-600">
                                        /month
                                    </span>
                                </p>
                                <a
                                    href="#"
                                    className="mt-10 block w-full rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                                >
                                    Subscribe Now
                                </a>
                                <p className="mt-6 text-xs leading-5 text-gray-600">
                                    Cancel anytime. No hidden fees.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
