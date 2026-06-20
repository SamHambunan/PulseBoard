import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { authApi } from '@/api/auth';

// validation rules
const loginSchema = z.object({
    email: z.email('Please enter a valid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
});

type LoginFormInputs = z.infer<typeof loginSchema>;

export default function LoginPage() {
    const setAuth = useAuthStore((s) => s.setAuth);
    const navigate = useNavigate();

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<LoginFormInputs>({
        resolver: zodResolver(loginSchema),
    });

    const onSubmit = async (data: LoginFormInputs) => {
        try {
            const res = await authApi.login(data);
            setAuth(res.data.token, res.data.user, res.data.tenant);
            navigate('/dashboards');
        } catch (error) {
            console.error('login failed', error);
            alert('login failed. please check your credentials.');
        }
    };

    return (
        // main background uses the off-white from the reference
        <div className="min-h-screen bg-background flex items-center justify-center p-4 lg:p-8">

            {/* the split card container: massive, rounded, soft shadow */}
            <div className="bg-surface rounded-2xl shadow-soft flex flex-col md:flex-row w-full max-w-4xl overflow-hidden">

                {/* left side: decorative branding area (hidden on tiny screens) */}
                <div className="hidden md:flex md:w-5/12 bg-primary p-10 flex-col justify-between text-white relative overflow-hidden">
                    {/* subtle background glow effect */}
                    <div className="absolute -top-24 -left-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>

                    <div className="relative z-10">
                        {/* faux logo */}
                        <div className="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-8 backdrop-blur-sm">
                            <span className="font-bold text-2xl tracking-tighter">Pb</span>
                        </div>

                        <h2 className="text-3xl font-bold mb-4 leading-tight">Welcome to Pulseboard</h2>
                        <p className="text-white/80 leading-relaxed text-sm">
                            Monitor your critical metrics in real-time, build beautiful dashboards, and collaborate seamlessly with your entire team.
                        </p>
                    </div>

                    {/* using the pastel accents from the reference image for decorative flair */}
                    <div className="relative z-10 flex gap-3 pb-4">
                        <div className="w-8 h-1.5 rounded-full bg-accent-pink"></div>
                        <div className="w-8 h-1.5 rounded-full bg-accent-orange"></div>
                        <div className="w-8 h-1.5 rounded-full bg-accent-green"></div>
                    </div>
                </div>

                {/* right side: the form area */}
                <div className="w-full md:w-7/12 p-8 lg:p-12 flex flex-col justify-center">
                    <div className="max-w-sm mx-auto w-full">

                        <div className="mb-8">
                            <h3 className="text-2xl font-bold text-text-main mb-2">Sign in</h3>
                            <p className="text-text-muted text-sm">Please enter your details to access your workspace.</p>
                        </div>

                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">Email Address</label>
                                <input
                                    type="email"
                                    placeholder="name@company.com"
                                    {...register('email')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.email && (
                                    <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.email.message}</p>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1.5">
                                    <label className="block text-sm font-semibold text-text-main">Password</label>
                                    <a href="#" className="text-xs text-primary hover:underline font-medium">Forgot password?</a>
                                </div>
                                <input
                                    type="password"
                                    placeholder="••••••••"
                                    {...register('password')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.password && (
                                    <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.password.message}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full bg-primary text-white rounded-xl py-3 text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-50 mt-2 shadow-soft shadow-primary/30"
                            >
                                {isSubmitting ? 'signing in...' : 'Sign in'}
                            </button>
                        </form>

                        {/* the missing prompt you asked for! */}
                        <p className="text-center text-sm text-text-muted mt-8">
                            Don't have an account?{' '}
                            <Link to="/register" className="text-primary font-semibold hover:underline">
                                Sign up here
                            </Link>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    );
}