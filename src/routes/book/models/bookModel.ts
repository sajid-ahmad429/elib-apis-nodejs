import mongoose, { Schema } from "mongoose";
import { IBook } from "../bookTypes";

const bookSchema = new Schema<IBook>(
  {
    title: {
      type: String,
      required: true,
      trim: true,
    },

    // Author should be an ObjectId reference to the User model
    author: {
      type: Schema.Types.ObjectId,
      ref: "userModel",
      required: true,
    },

    genre: {
      type: String,
      required: true,
      trim: true,
    },

    coverImage: {
      type: String,
      required: true,
      trim: true,
    },

    file: {
      type: String,
      required: true,
      trim: true,
    },

    isbn: {
      type: String,
      required: true,
      unique: true,
      trim: true,
    },

    publishedDate: {
      type: Date,
      required: true,
    },

    category: {
      type: String,
      required: true,
      trim: true,
    },

    language: {
      type: String,
      trim: true,
      default: "",
    },

    pages: {
      type: Number,
      default: 0,
    },

    publisher: {
      type: String,
      trim: true,
      default: "",
    },

    price: {
      type: Number,
      required: true,
    },

    status: {
      type: String,
      enum: ["available", "unavailable", "archived"],
      default: "available",
    },

    trash: {
      type: Boolean,
      default: false,
    },
  },
  {
    timestamps: true,
    collection: "tbl_books",
  }
);

const Book = mongoose.model<IBook>("bookModel", bookSchema);

export default Book;