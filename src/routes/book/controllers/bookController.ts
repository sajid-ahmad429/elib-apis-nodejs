/* eslint-disable @typescript-eslint/no-explicit-any */
import { validationResult } from "express-validator";
import Book from "../models/bookModel";
import { Request, Response } from "express";
import cloudinary from "../../../config/cloudinary";
import streamifier from "streamifier";
import fs from "fs";
import path from "path";

const allowedImageMimeTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
const allowedFileMimeTypes = ["application/pdf"];

/**
 * Uploads a buffer to Cloudinary in a specified folder.
 */
const uploadToCloudinary = (
  file: Express.Multer.File,
  folder: string
): Promise<string> => {
  return new Promise((resolve, reject) => {
    const isPDF = file.mimetype === "application/pdf";

    const uploadStream = cloudinary.uploader.upload_stream(
      {
        folder,
        resource_type: isPDF ? "raw" : "image",
        use_filename: true,
        unique_filename: false,
        filename_override: file.originalname,
      },
      (error, result) => {
        if (error || !result) {
          console.error("Cloudinary upload error:", error);
          return reject(new Error("Cloudinary upload failed"));
        }

        let fileUrl = result.secure_url;
        if (isPDF && !fileUrl.endsWith(".pdf")) {
          fileUrl += ".pdf";
        }

        resolve(fileUrl);
      }
    );

    streamifier.createReadStream(file.buffer).pipe(uploadStream);
  });
};

/**
 * Saves file buffer to local folder at public/data/upload
 */
const saveFileLocally = (file: Express.Multer.File, folder: string): string => {
  const uploadDir = path.join(__dirname, "..", "..", "public", "data", folder);
  if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
  }

  const filePath = path.join(uploadDir, file.originalname);
  fs.writeFileSync(filePath, file.buffer);

  return filePath;
};

const createBook = async (req: Request, res: Response): Promise<void> => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    res.status(422).json({
      status: 422,
      message: "Validation failed",
      errors: errors.array(),
    });
    return;
  }

  try {
    const {
      title,
      author,
      isbn,
      publishedDate,
      category,
      language,
      pages,
      publisher,
      price,
      status,
      genre,
    } = req.body;

    // Validate published date
    const parsedDate = new Date(publishedDate);
    if (isNaN(parsedDate.getTime())) {
      res.status(400).json({ status: 400, message: "Invalid published date" });
      return;
    }

    // Check for existing ISBN
    const existingBook = await Book.findOne({ isbn: isbn.trim() });
    if (existingBook) {
      res.status(400).json({ status: 400, message: "Book with this ISBN already exists" });
      return;
    }

    // Ensure multer files exist
    const files = req.files as { [fieldname: string]: Express.Multer.File[] } | undefined;
    if (!files) {
      res.status(400).json({ status: 400, message: "No files uploaded" });
      return;
    }

    const coverImage = files.coverImage?.[0];
    const bookFile = files.file?.[0];

    // Validate coverImage
    if (!coverImage) {
      res.status(400).json({ status: 400, message: "Cover image is required" });
      return;
    }
    if (!allowedImageMimeTypes.includes(coverImage.mimetype)) {
      res.status(400).json({ status: 400, message: "Invalid cover image format. Allowed formats: JPEG, PNG, GIF, WEBP" });
      return;
    }

    // Validate bookFile
    if (!bookFile) {
      res.status(400).json({ status: 400, message: "Book file (PDF) is required" });
      return;
    }
    if (!allowedFileMimeTypes.includes(bookFile.mimetype)) {
      res.status(400).json({ status: 400, message: "Invalid book file format. Only PDF files are allowed" });
      return;
    }

    // Save locally
    saveFileLocally(coverImage, "upload");
    saveFileLocally(bookFile, "upload");

    // Upload to Cloudinary
    const coverImagePath = await uploadToCloudinary(coverImage, "books/coverImages");
    const bookFilePath = await uploadToCloudinary(bookFile, "books/files");

    // Save to MongoDB
    const newBook = new Book({
      title: title.trim(),
      genre: genre?.trim() || "",
      author: author.trim(),
      isbn: isbn.trim(),
      publishedDate: parsedDate,
      category: category.trim(),
      language: language?.trim() || "",
      pages: pages ? Number(pages) : 0,
      publisher: publisher?.trim() || "",
      price: Number(price),
      status: status || "available",
      coverImage: coverImagePath,
      file: bookFilePath,
    });

    await newBook.save();

    res.status(201).json({
      status: 201,
      message: "Book created successfully",
      data: newBook,
    });
  } catch (error: any) {
    console.error("Server error:", error?.message || error);
    res.status(500).json({
      status: 500,
      message: "Server error",
      error: error?.message || "Unknown error"
    });
  }
};

export { createBook };