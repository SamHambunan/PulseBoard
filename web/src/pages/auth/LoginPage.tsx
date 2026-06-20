import { useState } from 'react'; // added useState
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { authApi } from '@/api/auth';

const loginSchema = z.object({
    email: z.email('please enter a valid email address'),
    password: z.string().min(8, 'password must be at least 8 characters'),
});

type LoginFormInputs = z.infer<typeof loginSchema>;

export default function LoginPage() {
    const setAuth = useAuthStore((s) => s.setAuth);
    const navigate = useNavigate();
    // state to hold our backend error message
    const [rootError, setRootError] = useState<string>('');

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<LoginFormInputs>({
        resolver: zodResolver(loginSchema),
    });

    const onSubmit = async (data: LoginFormInputs) => {
        try {
            // clear any previous errors when they try again
            setRootError('');
            const res = await authApi.login(data);
            setAuth(res.data.token, res.data.user, res.data.tenant);
            navigate('/dashboards');
        } catch (error: any) {
            console.error('login failed', error);
            // check if the backend sent a specific message, otherwise show a generic one
            const message = error.response?.data?.message || 'invalid email or password. please try again.';
            setRootError(message);
        }
    };

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4 lg:p-8">
            <div className="bg-surface rounded-2xl shadow-soft flex flex-col md:flex-row w-full max-w-4xl overflow-hidden">

                {/* left side: branding */}
                <div className="hidden md:flex md:w-5/12 bg-primary p-10 flex-col justify-between text-white relative overflow-hidden">
                    <div className="absolute -top-24 -left-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div className="relative z-10">
                        <div className="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-8 backdrop-blur-sm">
                            <span className="font-bold text-2xl tracking-tighter">Pb</span>
                        </div>
                        <h2 className="text-3xl font-bold mb-4 leading-tight">welcome to pulseboard</h2>
                        <p className="text-white/80 leading-relaxed text-sm">
                            monitor your critical metrics in real-time, build beautiful dashboards, and collaborate seamlessly with your entire team.
                        </p>
                    </div>
                    <div className="relative z-10 flex gap-3 pb-4">
                        <div className="w-8 h-1.5 rounded-full bg-accent-pink"></div>
                        <div className="w-8 h-1.5 rounded-full bg-accent-orange"></div>
                        <div className="w-8 h-1.5 rounded-full bg-accent-green"></div>
                    </div>
                </div>

                {/* right side: form area */}
                <div className="w-full md:w-7/12 p-8 lg:p-12 flex flex-col justify-center">
                    <div className="max-w-sm mx-auto w-full">
                        <div className="mb-8">
                            <h3 className="text-2xl font-bold text-text-main mb-2">sign in</h3>
                            <p className="text-text-muted text-sm">please enter your details to access your workspace.</p>
                        </div>

                        {/* beautiful inline error banner using our pastel pink styles */}
                        {rootError && (
                            <div className="mb-6 p-3 rounded-xl bg-accent-pink border border-accent-pinktext/20 text-accent-pinktext text-sm font-medium flex items-center gap-2">
                                <svg className="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {rootError}
                            </div>
                        )}

                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                            {/* inputs remain exactly the same... */}
                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">email address</label>
                                <input
                                    type="email"
                                    placeholder="name@company.com"
                                    {...register('email')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.email && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.email.message}</p>}
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1.5">
                                    <label className="block text-sm font-semibold text-text-main">password</label>
                                    <a href="#" className="text-xs text-primary hover:underline font-medium">forgot password?</a>
                                </div>
                                <input
                                    type="password"
                                    placeholder="••••••••"
                                    {...register('password')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.password && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.password.message}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full bg-primary text-white rounded-xl py-3 text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-50 mt-2 shadow-soft shadow-primary/30"
                            >
                                {isSubmitting ? 'signing in...' : 'sign in'}
                            </button>
                        </form>

                        <p className="text-center text-sm text-text-muted mt-8">
                            don't have an account? <Link to="/register" className="text-primary font-semibold hover:underline">sign up here</Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}