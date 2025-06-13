import { Document, Types } from "mongoose";
import { IUser } from "../users/userTypes"; // Assuming IUser is your User interface

export interface IBook extends Document {
  _id: string;

  title: string; // Book title - required

  // Author reference - required
  // This can be the ObjectId or the populated IUser object
  author: Types.ObjectId | IUser;

  genre: string; // Book genre/category (e.g. Fiction, Sci-fi) - required

  coverImage: string; // URL or path for cover image - required

  file: string; // URL or path for digital file (pdf, epub, etc.) - required

  isbn: string; // ISBN number - required

  publishedDate: Date; // Publication date - required

  category: string; // Additional category/tag - required (if different from genre)

  language?: string; // Language of the book - optional

  pages?: number; // Number of pages - optional

  publisher?: string; // Publisher name - optional

  price: number; // Price of the book - required

  status?: "available" | "unavailable" | "archived"; // Book availability status - optional, default can be 'available'

  trash?: boolean; // Soft delete flag - optional, default false
}