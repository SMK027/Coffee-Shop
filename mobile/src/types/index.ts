export interface User {
  id: number;
  name: string;
  email: string;
  global_role: string;
}

export interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user: User;
}

export interface Drink {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  price: number;
  available: boolean;
  loyalty_points: number;
  sort_order: number;
  category: { id: number; name: string } | null;
  image_url: string | null;
}

export interface LoyaltyCard {
  id: number;
  card_number: string;
  full_name: string;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  points: number;
  has_employee_benefits: boolean;
}

export interface LoyaltyDiscount {
  id: number;
  name: string;
  description: string | null;
  points_cost: number;
  discount_type: 'fixed' | 'percent';
  discount_value: number;
  max_discount_amount: number | null;
  employee_only: boolean;
}

export interface OrderItem {
  id: number;
  drink_id: number | null;
  drink_name: string;
  quantity: number;
  unit_price: number;
  subtotal: number;
  custom_label: string | null;
}

export interface Order {
  id: number;
  customer_name: string;
  status: string;
  status_label: string;
  is_employee_order: boolean;
  total_amount: number;
  discount_amount: number;
  loyalty_discount_amount: number;
  loyalty_points_spent: number;
  notes: string | null;
  created_at: string;
  completed_at: string | null;
  handled_by: string | null;
  items?: OrderItem[];
  loyalty_card?: { card_number: string; full_name: string; points: number } | null;
  loyalty_discounts?: Array<{
    id: number;
    name: string;
    points_spent: number;
    discount_amount: number;
  }>;
}

export interface OrderStatus {
  key: string;
  label: string;
  color: string | null;
  is_terminal: boolean;
  is_active: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

export interface NewOrderItem {
  drink_id?: number;
  custom_label?: string;
  custom_price?: number;
  quantity: number;
}

export interface CreateOrderPayload {
  customer_name?: string;
  loyalty_card_number?: string;
  card_pin?: string;
  is_employee_order?: boolean;
  loyalty_discount_ids?: number[];
  notes?: string;
  items: NewOrderItem[];
}
