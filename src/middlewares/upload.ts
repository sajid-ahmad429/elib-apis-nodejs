import multer from "multer";

const fileFilter = (_req: unknown, file: Express.Multer.File, cb: multer.FileFilterCallback) => {
  const allowedMimeTypes = [
    "image/jpeg",
    "image/png",
    "application/pdf",
    "application/epub+zip",
  ];
  if (allowedMimeTypes.includes(file.mimetype)) {
    cb(null, true);
  } else {
    cb(new Error("Unsupported file type!"));
  }
};

// Use memory storage so multer does NOT save files on disk automatically
const memoryStorage = multer.memoryStorage();

export const bookUpload = multer({
  storage: memoryStorage,
  fileFilter,
  limits: {
    fileSize: 30 * 1024 * 1024, // 30MB
  },
});
