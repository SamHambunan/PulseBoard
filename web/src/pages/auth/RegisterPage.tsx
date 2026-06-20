import { useState } from 'react'; // added useState
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { authApi } from '@/api/auth';

const registerSchema = z.object({
    name: z.string().min(2, 'name must be at least 2 characters'),
    tenant_name: z.string().min(2, 'workspace name is required'),
    email: z.string().email('please enter a valid email address'),
    password: z.string().min(8, 'password must be at least 8 characters'),
});

type RegisterFormInputs = z.infer<typeof registerSchema>;

export default function RegisterPage() {
    const setAuth = useAuthStore((s) => s.setAuth);
    const navigate = useNavigate();
    // state to hold our backend error message
    const [rootError, setRootError] = useState<string>('');

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormInputs>({
        resolver: zodResolver(registerSchema),
    });

    const onSubmit = async (data: RegisterFormInputs) => {
        try {
            setRootError('');
            const res = await authApi.register(data);
            setAuth(res.data.token, res.data.user, res.data.tenant);
            navigate('/dashboards');
        } catch (error: any) {
            console.error('registration failed', error);
            // fallback message if backend doesn't provide a specific one
            const message = error.response?.data?.message || 'registration failed. this email might already be taken.';
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
                        <div className="inline-flex h-12 px-6 bg-white/10 rounded-xl items-center justify-center mb-8 backdrop-blur-sm border border-white/20">
                            <span className="font-mono text-sm tracking-widest text-white/80">YOUR LOGO</span>
                        </div>
                        <h2 className="text-3xl font-bold mb-4 leading-tight">start your journey</h2>
                        <p className="text-white/80 leading-relaxed text-sm">
                            create your workspace, invite your team, and start visualizing your data in minutes. no credit card required.
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
                            <h3 className="text-2xl font-bold text-text-main mb-2">create an account</h3>
                            <p className="text-text-muted text-sm">set up your profile and workspace.</p>
                        </div>

                        {/* the error banner */}
                        {rootError && (
                            <div className="mb-6 p-3 rounded-xl bg-accent-pink border border-accent-pinktext/20 text-accent-pinktext text-sm font-medium flex items-center gap-2">
                                <svg className="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {rootError}
                            </div>
                        )}

                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                            {/* inputs remain exactly the same... */}
                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">your name</label>
                                <input
                                    type="text"
                                    placeholder="jane doe"
                                    {...register('name')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.name && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.name.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">workspace name</label>
                                <input
                                    type="text"
                                    placeholder="acme corp"
                                    {...register('tenant_name')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.tenant_name && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.tenant_name.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">email address</label>
                                <input
                                    type="email"
                                    placeholder="jane@acme.com"
                                    {...register('email')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.email && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.email.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">password</label>
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
                                className="w-full bg-primary text-white rounded-xl py-3 text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-50 mt-4 shadow-soft shadow-primary/30"
                            >
                                {isSubmitting ? 'creating account...' : 'create account'}
                            </button>
                        </form>

                        <p className="text-center text-sm text-text-muted mt-8">
                            already have an account? <Link to="/login" className="text-primary font-semibold hover:underline">sign in</Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}