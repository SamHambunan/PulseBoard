import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { authApi } from '@/api/auth';

// validation rules
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

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormInputs>({
        resolver: zodResolver(registerSchema),
    });

    const onSubmit = async (data: RegisterFormInputs) => {
        try {
            const res = await authApi.register(data);
            setAuth(res.data.token, res.data.user, res.data.tenant);
            navigate('/dashboards');
        } catch (error) {
            console.error('registration failed', error);
            alert('registration failed. email might be taken.');
        }
    };

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4 lg:p-8">

            {/* the split card container */}
            <div className="bg-surface rounded-2xl shadow-soft flex flex-col md:flex-row w-full max-w-4xl overflow-hidden">

                {/* left side: branding and placeholder logo */}
                <div className="hidden md:flex md:w-5/12 bg-primary p-10 flex-col justify-between text-white relative overflow-hidden">
                    <div className="absolute -top-24 -left-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>

                    <div className="relative z-10">

                        {/* explicit logo placeholder for you to replace later */}
                        <div className="inline-flex h-12 px-6 bg-white/10 rounded-xl items-center justify-center mb-8 backdrop-blur-sm border border-white/20">
                            <span className="font-mono text-sm tracking-widest text-white/80">YOUR LOGO</span>
                        </div>

                        <h2 className="text-3xl font-bold mb-4 leading-tight">Start your journey</h2>
                        <p className="text-white/80 leading-relaxed text-sm">
                            Create your workspace, invite your team, and start visualizing your data in minutes. no credit card required.
                        </p>
                    </div>

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
                            <h3 className="text-2xl font-bold text-text-main mb-2">Create an account</h3>
                            <p className="text-text-muted text-sm">Set up your profile and workspace.</p>
                        </div>

                        {/* form with 4 inputs using the new premium styling */}
                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">Your Name</label>
                                <input
                                    type="text"
                                    placeholder="Jane Doe"
                                    {...register('name')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.name && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.name.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">Workspace Name</label>
                                <input
                                    type="text"
                                    placeholder="Acme Corp"
                                    {...register('tenant_name')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.tenant_name && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.tenant_name.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">Email Address</label>
                                <input
                                    type="email"
                                    placeholder="jane@acme.com"
                                    {...register('email')}
                                    className="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-background focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all text-text-main placeholder:text-gray-400"
                                />
                                {errors.email && <p className="text-accent-pinktext text-xs mt-1.5 font-medium">{errors.email.message}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold mb-1.5 text-text-main">Password</label>
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
                                {isSubmitting ? 'Creating Account...' : 'Create Account'}
                            </button>
                        </form>

                        <p className="text-center text-sm text-text-muted mt-8">
                            Already have an account?{' '}
                            <Link to="/login" className="text-primary font-semibold hover:underline">
                                Sign in
                            </Link>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    );
}